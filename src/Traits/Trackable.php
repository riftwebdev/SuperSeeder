<?php

namespace Riftweb\SuperSeeder\Traits;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use RuntimeException;
use Throwable;

trait Trackable
{
    /**
     * @var array<string, array<string, list<int|string>>>
     */
    protected array $tracked = [];

    public function run(): void
    {
        if (! $this->shouldRun()) {
            return;
        }

        $startedAt = microtime(true);
        $callback = function () use ($startedAt): void {
            $this->up();
            $this->markAsRun((int) round((microtime(true) - $startedAt) * 1000));
        };

        if ($this->withinTransaction()) {
            DB::transaction($callback);

            return;
        }

        try {
            $callback();
        } catch (Throwable $exception) {
            if (! $this->wasRecentlyCreatedTrackingFailure($exception)) {
                throw $exception;
            }

            $this->down();

            throw $exception;
        }
    }

    public function shouldRun(): bool
    {
        $executor = app('superseeder.executor');
        $service = app('superseeder.service');

        if (! $this->matchesEnvironment() || ! $this->matchesRequestedTags()) {
            return false;
        }

        return config('superseeder.bypass') || $executor->isForced() || $service->seederDoesntExists(static::class);
    }

    abstract protected function up(): void;

    public function markAsRun(?int $executionTimeMs = null): void
    {
        $service = app('superseeder.service');
        $executor = app('superseeder.executor');
        $trackedRecords = $this->resolveTrackedRecords();

        if (! $service->store(static::class, $executor->currentBatch(), [
            'status' => 'ran',
            'execution_time_ms' => $executionTimeMs,
            'tracked_records' => $trackedRecords === [] ? null : $trackedRecords,
            'record_hash' => $trackedRecords === [] ? null : $this->hashTrackedRecords($trackedRecords),
            'seeder_hash' => $this->hashSeederClass(),
        ])) {
            throw new RuntimeException(sprintf('Unable to record execution for %s.', static::class));
        }
    }

    /**
     * @return array<string, array<string, list<int|string>>>
     */
    public function seededRecords(): array
    {
        return $this->tracked;
    }

    /**
     * @param  mixed  $value
     * @return mixed
     */
    public function track(mixed $value): mixed
    {
        $this->captureTrackedValue($value);

        return $value;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<int|string>|Collection<int, int|string>|EloquentCollection<int, Model>|Model|QueryBuilder  $records
     */
    public function pruneModels(string $modelClass, array|Collection|EloquentCollection|Model|QueryBuilder $records, ?bool $force = null): int
    {
        /** @var Model $model */
        $model = new $modelClass;
        $keys = collect($records instanceof QueryBuilder ? $records->pluck($model->getKeyName()) : $records)
            ->map(function (mixed $record) use ($model): int|string|null {
                if ($record instanceof Model) {
                    return $record->getKey();
                }

                return $record;
            })
            ->filter(fn (mixed $key): bool => $key !== null)
            ->values()
            ->all();

        if ($keys === []) {
            return 0;
        }

        $query = $modelClass::query()->whereKey($keys);
        $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($modelClass), true);

        if ($force === true && method_exists($query, 'forceDelete')) {
            return $query->forceDelete();
        }

        if ($force === false || ! $usesSoftDeletes) {
            return $query->delete();
        }

        return $query->delete();
    }

    /**
     * @return list<string>
     */
    public function seederTags(): array
    {
        return property_exists($this, 'tags') ? array_values((array) $this->tags) : [];
    }

    public function runsWithinTransaction(): bool
    {
        return $this->withinTransaction();
    }

    protected function withinTransaction(): bool
    {
        return ! property_exists($this, 'withinTransaction') || (bool) $this->withinTransaction;
    }

    protected function matchesEnvironment(): bool
    {
        $environment = app()->environment();
        $only = property_exists($this, 'environments') ? (array) $this->environments : (property_exists($this, 'only') ? (array) $this->only : []);
        $except = property_exists($this, 'except') ? (array) $this->except : [];

        if ($only !== [] && ! in_array($environment, $only, true)) {
            return false;
        }

        return ! in_array($environment, $except, true);
    }

    protected function matchesRequestedTags(): bool
    {
        $requestedTags = app('superseeder.executor')->requestedTags();

        if ($requestedTags === []) {
            return true;
        }

        $tags = property_exists($this, 'tags') ? (array) $this->tags : [];

        return array_intersect($requestedTags, $tags) !== [];
    }

    /**
     * @return array<string, array<string, list<int|string>>>
     */
    protected function resolveTrackedRecords(): array
    {
        $records = $this->tracked;
        $method = (new ReflectionClass($this))->getMethod('seededRecords');

        if ($method->getFileName() !== __FILE__) {
            $records = $this->mergeTrackedRecords($records, $this->seededRecords());
        }

        return $records;
    }

    protected function captureTrackedValue(mixed $value): void
    {
        if ($value instanceof Model) {
            $this->trackModel($value);

            return;
        }

        if ($value instanceof EloquentCollection || $value instanceof Collection) {
            $value->each(fn (mixed $item) => $this->captureTrackedValue($item));

            return;
        }

        if (is_array($value) && $this->looksLikeTrackedRecordMap($value)) {
            $this->tracked = $this->mergeTrackedRecords($this->tracked, $value);
        }
    }

    protected function trackModel(Model $model): void
    {
        if (! $model->exists) {
            return;
        }

        $table = $model->getTable();
        $keyName = $model->getKeyName();
        $key = $model->getKey();

        if ($key === null) {
            return;
        }

        $this->tracked[$table][$keyName] ??= [];
        $this->tracked[$table][$keyName][] = $key;
        $this->tracked[$table][$keyName] = array_values(array_unique($this->tracked[$table][$keyName], SORT_REGULAR));
    }

    protected function looksLikeTrackedRecordMap(array $value): bool
    {
        return $value !== [] && collect($value)->every(function (mixed $columns): bool {
            return is_array($columns) && collect($columns)->every(fn (mixed $ids): bool => is_array($ids));
        });
    }

    /**
     * @param  array<string, array<string, list<int|string>>>  $left
     * @param  array<string, array<string, list<int|string>>>  $right
     * @return array<string, array<string, list<int|string>>>
     */
    protected function mergeTrackedRecords(array $left, array $right): array
    {
        $merged = $left;

        foreach ($right as $table => $columns) {
            foreach ($columns as $column => $ids) {
                $merged[$table][$column] ??= [];
                $merged[$table][$column] = array_values(array_unique([
                    ...$merged[$table][$column],
                    ...$ids,
                ], SORT_REGULAR));
            }
        }

        return $merged;
    }

    /**
     * @param  array<string, array<string, list<int|string>>>  $trackedRecords
     */
    protected function hashTrackedRecords(array $trackedRecords): string
    {
        $normalizedRecords = $this->normalizeTrackedRecords($trackedRecords);
        $rows = collect($normalizedRecords)
            ->mapWithKeys(function (array $columns, string $table): array {
                $serializedColumns = collect($columns)->map(function (array $ids, string $column) use ($table): array {
                    if ($ids === []) {
                        return [$column => []];
                    }

                    return [
                        $column => DB::table($table)
                            ->whereIn($column, $ids)
                            ->orderBy($column)
                            ->get()
                            ->map(fn (object $row): array => (array) $row)
                            ->all(),
                    ];
                })->all();

                return [$table => $serializedColumns];
            })
            ->all();

        return hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, array<string, list<int|string>>>  $trackedRecords
     * @return array<string, array<string, list<int|string>>>
     */
    protected function normalizeTrackedRecords(array $trackedRecords): array
    {
        ksort($trackedRecords);

        foreach ($trackedRecords as $table => $columns) {
            ksort($columns);

            foreach ($columns as $column => $ids) {
                sort($ids);
                $columns[$column] = array_values(array_unique($ids, SORT_REGULAR));
            }

            $trackedRecords[$table] = $columns;
        }

        return $trackedRecords;
    }

    protected function hashSeederClass(): ?string
    {
        $fileName = (new ReflectionClass($this))->getFileName();

        return $fileName ? hash_file('sha256', $fileName) ?: null : null;
    }

    protected function wasRecentlyCreatedTrackingFailure(Throwable $exception): bool
    {
        return $exception instanceof RuntimeException
            && str_contains($exception->getMessage(), 'Unable to record execution for');
    }

    abstract public function down(): void;
}
