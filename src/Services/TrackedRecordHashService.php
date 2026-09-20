<?php

namespace Riftweb\SuperSeeder\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TrackedRecordHashService
{
    /**
     * @param  array<string, array<string, list<int|string>>>  $trackedRecords
     */
    public function hash(array $trackedRecords, bool $requireUniqueColumns = true): ?string
    {
        $normalizedRecords = $this->normalize($trackedRecords);

        if ($requireUniqueColumns) {
            foreach ($normalizedRecords as $table => $columns) {
                foreach (array_keys($columns) as $column) {
                    if (! $this->supportsRowHashing($table, $column)) {
                        return null;
                    }
                }
            }
        }

        $rows = collect($normalizedRecords)
            ->mapWithKeys(function (array $columns, string $table): array {
                $serializedColumns = collect($columns)->map(function (array $ids, string $column) use ($table): array {
                    if ($ids === []) {
                        return [$column => []];
                    }

                    $query = DB::table($table)
                        ->whereIn($column, $ids)
                        ->orderBy($column);

                    if ($primaryKey = $this->primaryKeyColumn($table)) {
                        $query->orderBy($primaryKey);
                    }

                    return [
                        $column => $query
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
    public function normalize(array $trackedRecords): array
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

    /**
     * @param  array<string, array<string, list<int|string>>>  $trackedRecords
     * @return array<string, array<string, list<int|string>>>
     */
    public function missingRows(array $trackedRecords): array
    {
        $missingRows = [];

        foreach ($this->normalize($trackedRecords) as $table => $columns) {
            foreach ($columns as $column => $ids) {
                if ($ids === []) {
                    continue;
                }

                $existingIds = DB::table($table)
                    ->whereIn($column, $ids)
                    ->pluck($column)
                    ->map(fn (mixed $id): string => (string) $id)
                    ->all();

                $missingIds = collect($ids)
                    ->mapWithKeys(fn (string|int $id): array => [(string) $id => $id])
                    ->except($existingIds)
                    ->values()
                    ->all();

                if ($missingIds !== []) {
                    $missingRows[$table][$column] = $missingIds;
                }
            }
        }

        return $missingRows;
    }

    protected function supportsRowHashing(string $table, string $column): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index): bool => (($index['primary'] ?? false) || ($index['unique'] ?? false))
                && ($index['columns'] ?? []) === [$column]);
    }

    protected function primaryKeyColumn(string $table): ?string
    {
        $primaryIndex = collect(Schema::getIndexes($table))
            ->first(fn (array $index): bool => ($index['primary'] ?? false) && count($index['columns'] ?? []) === 1);

        return $primaryIndex['columns'][0] ?? null;
    }
}
