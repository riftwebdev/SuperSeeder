<?php

namespace Riftweb\SuperSeeder\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Riftweb\SuperSeeder\Exceptions\RollbackBlockedException;
use Riftweb\SuperSeeder\Models\SeederExecution;
use RuntimeException;

class SeederRollbackService
{
    public function __construct(
        protected SeederExecutionService $seederExecutionService,
        protected TrackedRecordHashService $trackedRecordHashService,
    ) {}

    /**
     * @param  Collection<int, SeederExecution>|array<int, SeederExecution>  $executions
     * @return array{seeders: list<class-string>, warnings: list<string>}
     */
    public function rollbackBatch(Collection|array $executions, bool $dryRun = false, bool $cascade = false): array
    {
        $rolledBackSeeders = [];
        $warnings = [];

        foreach (collect($executions) as $execution) {
            $seeder = $execution->seeder;

            if ($this->seederExecutionService->getLatestExecutionForSeeder($seeder)?->id !== $execution->id) {
                continue;
            }

            $instance = app($seeder);
            $trackedRecords = $this->resolveTrackedRecords($execution);
            $dependencies = $this->findDependencies($trackedRecords);
            $warnings = [...$warnings, ...$this->driftWarnings($instance, $execution, $trackedRecords)];

            if ($dependencies->isNotEmpty() && ! $cascade) {
                $dependency = $dependencies->first();

                throw new RollbackBlockedException(sprintf(
                    'Cannot rollback %s: %d %s record(s) depend on seeded %s IDs [%s]. Use --cascade to force.',
                    class_basename($seeder),
                    $dependency['count'],
                    $dependency['table'],
                    $dependency['seeded_table'],
                    implode(', ', $dependency['ids']),
                ));
            }

            if (! $dryRun) {
                $callback = function () use ($dependencies, $instance, $execution, $seeder): void {
                    if ($dependencies->isNotEmpty()) {
                        $this->deleteDependencies($dependencies);
                    }

                    $instance->down();

                    if (! $this->seederExecutionService->deleteExecution($execution->id)) {
                        throw new RuntimeException(sprintf('Unable to remove the tracking record for %s.', $seeder));
                    }
                };

                DB::transaction($callback);
            }

            $rolledBackSeeders[] = $seeder;
        }

        return [
            'seeders' => $rolledBackSeeders,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * @param  array<string, array<string, list<int|string>>>  $trackedRecords
     * @return Collection<int, array{table: string, column: string, seeded_table: string, ids: list<int|string>, count: int}>
     */
    protected function findDependencies(array $trackedRecords): Collection
    {
        return collect($trackedRecords)
            ->flatMap(function (array $columns, string $seededTable): Collection {
                return collect($columns)->flatMap(function (array $ids, string $seededColumn) use ($seededTable): Collection {
                    if ($ids === []) {
                        return collect();
                    }

                    return collect(Schema::getTables())->flatMap(function (array $table) use ($seededTable, $seededColumn, $ids): Collection {
                        return collect(Schema::getForeignKeys($table['name']))
                            ->filter(fn (array $foreignKey): bool => $foreignKey['foreign_table'] === $seededTable
                                && $foreignKey['foreign_columns'] === [$seededColumn]
                                && count($foreignKey['columns']) === 1)
                            ->map(function (array $foreignKey) use ($table, $seededTable, $ids): array {
                                return [
                                    'table' => $table['name'],
                                    'column' => $foreignKey['columns'][0],
                                    'seeded_table' => $seededTable,
                                    'ids' => $ids,
                                    'count' => DB::table($table['name'])->whereIn($foreignKey['columns'][0], $ids)->count(),
                                ];
                            })
                            ->filter(fn (array $dependency): bool => $dependency['count'] > 0);
                    });
                });
            })
            ->values();
    }

    /**
     * @param  Collection<int, array{table: string, column: string, seeded_table: string, ids: list<int|string>, count: int}>  $dependencies
     */
    protected function deleteDependencies(Collection $dependencies): void
    {
        $dependencies->each(function (array $dependency): void {
            DB::table($dependency['table'])
                ->whereIn($dependency['column'], $dependency['ids'])
                ->delete();
        });
    }

    /**
     * @return array<string, array<string, list<int|string>>>
     */
    protected function resolveTrackedRecords(SeederExecution $execution): array
    {
        return is_array($execution->tracked_records) ? $execution->tracked_records : [];
    }

    /**
     * @param  array<string, array<string, list<int|string>>>  $trackedRecords
     * @return list<string>
     */
    protected function driftWarnings(object $seeder, SeederExecution $execution, array $trackedRecords): array
    {
        $warnings = [];
        $currentSeederHash = $this->hashSeeder($seeder);
        $missingTrackedRows = $this->trackedRecordHashService->missingRows($trackedRecords);

        if ($execution->seeder_hash && $currentSeederHash && $execution->seeder_hash !== $currentSeederHash) {
            $warnings[] = sprintf(
                '%s changed since it last ran; verify the rollback still matches the seeded data.',
                class_basename($execution->seeder),
            );
        }

        foreach ($missingTrackedRows as $table => $columns) {
            foreach ($columns as $column => $ids) {
                $warnings[] = sprintf(
                    '%s is missing tracked %s %s IDs [%s]; rollback may be operating on partially removed data.',
                    class_basename($execution->seeder),
                    $table,
                    $column,
                    implode(', ', $ids),
                );
            }
        }

        $recordHash = $trackedRecords === [] ? null : $this->trackedRecordHashService->hash($trackedRecords);

        if ($execution->record_hash && $recordHash && $execution->record_hash !== $recordHash) {
            $warnings[] = sprintf(
                '%s tracked records drifted since the last seed run; rollback may affect manually edited data.',
                class_basename($execution->seeder),
            );
        }

        return $warnings;
    }

    protected function hashSeeder(object $seeder): ?string
    {
        $fileName = (new ReflectionClass($seeder))->getFileName();

        return $fileName ? hash_file('sha256', $fileName) ?: null : null;
    }
}
