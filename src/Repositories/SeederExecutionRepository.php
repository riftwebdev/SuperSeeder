<?php

namespace Riftweb\SuperSeeder\Repositories;

use Illuminate\Support\Collection;
use Riftweb\SuperSeeder\Models\SeederExecution;

class SeederExecutionRepository
{
    /**
     * @param  array{
     *     seeder: class-string,
     *     batch: int,
     *     status?: string,
     *     execution_time_ms?: int|null,
     *     tracked_records?: array<string, array<string, list<int|string>>>|null,
     *     record_hash?: string|null,
     *     seeder_hash?: string|null
     * }  $data
     */
    public function store(array $data): SeederExecution
    {
        return SeederExecution::create($data);
    }

    public function getNextBatch(): int
    {
        return $this->getLatestBatch() + 1;
    }

    public function getLatestBatch(): int
    {
        return SeederExecution::max('batch') ?? 0;
    }

    public function seederDoesntExists(string $seeder): bool
    {
        return SeederExecution::where('seeder', $seeder)->doesntExist();
    }

    public function getByBatch(int $batch): Collection
    {
        return SeederExecution::where('batch', $batch)
            ->orderByDesc('id')
            ->get();
    }

    public function getLatestExecutionForSeeder(string $seeder): ?SeederExecution
    {
        return SeederExecution::where('seeder', $seeder)
            ->latest('id')
            ->first();
    }

    public function getLatestExecutions(): Collection
    {
        return SeederExecution::query()
            ->orderByDesc('id')
            ->get()
            ->unique('seeder')
            ->values();
    }

    public function getTrackedSeederClasses(): Collection
    {
        return SeederExecution::query()
            ->select('seeder')
            ->distinct()
            ->pluck('seeder');
    }

    public function deleteExecution(int $id): bool
    {
        return SeederExecution::whereKey($id)->delete() > 0;
    }

    public function truncate(): bool
    {
        SeederExecution::truncate();

        return true;
    }
}
