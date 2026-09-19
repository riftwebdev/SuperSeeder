<?php

namespace Riftweb\SuperSeeder\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Riftweb\SuperSeeder\Exceptions\RollbackBlockedException;
use RuntimeException;

class SeederRollbackService
{
    public function __construct(
        protected SeederExecutionService $seederExecutionService,
    ) {}

    /**
     * @param  list<class-string>  $seeders
     * @return list<class-string>
     */
    public function rollbackBatch(array $seeders, bool $dryRun = false, bool $cascade = false): array
    {
        $rolledBackSeeders = [];

        foreach ($seeders as $seeder) {
            if ($this->seederExecutionService->seederDoesntExists($seeder)) {
                continue;
            }

            $instance = app($seeder);
            $dependencies = $this->findDependencies($instance);

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
                $instance->down();

                if (! $this->seederExecutionService->deleteBySeeder($seeder)) {
                    throw new RuntimeException(sprintf('Unable to remove the tracking record for %s.', $seeder));
                }
            }

            $rolledBackSeeders[] = $seeder;
        }

        return $rolledBackSeeders;
    }

    /**
     * @return Collection<int, array{table: string, seeded_table: string, ids: list<int|string>, count: int}>
     */
    protected function findDependencies(object $seeder): Collection
    {
        if (! method_exists($seeder, 'seededRecords')) {
            return collect();
        }

        return collect($seeder->seededRecords())
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
}
