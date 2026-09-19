<?php

namespace Riftweb\SuperSeeder\Repositories;

use Illuminate\Support\Collection;
use Riftweb\SuperSeeder\Models\SeederExecution;

class SeederExecutionRepository
{
    /**
     * @param  array{seeder: class-string, batch: int}  $data
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

    public function deleteBySeeder(string $seeder): bool
    {
        return SeederExecution::where('seeder', $seeder)->delete() > 0;
    }

    public function truncate(): bool
    {
        SeederExecution::truncate();

        return true;
    }
}
