<?php

namespace Riftweb\SuperSeeder\Repositories;

use Illuminate\Support\Collection;
use Riftweb\SuperSeeder\Models\SeederExecution;

class SeederExecutionRepository
{
    public function store(array $data): ?SeederExecution
    {
        try {
            return SeederExecution::create($data);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    public function getNextBatch(): int
    {
        try {
            return $this->getLatestBatch() + 1;
        } catch (\Throwable $e) {
            report($e);
            return 1;
        }
    }
    public function getLatestBatch(): int
    {
        try {
            $batch = SeederExecution::max('batch');

            if (!is_null($batch)) {
                return $batch;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return 0;
    }

    public function seederExists(string $seeder): bool
    {
        return SeederExecution::where('seeder', $seeder)->exists();
    }
    public function seederDoesntExists(string $seeder): bool
    {
        return SeederExecution::where('seeder', $seeder)->doesntExist();
    }

    public function findBySeeder(string $seeder): ?SeederExecution
    {
        return SeederExecution::where('seeder', $seeder)->first();
    }

    public function getByBatch(int $batch): Collection
    {
        try {
            return SeederExecution::where('batch', $batch)
                ->orderByDesc('id')
                ->get();
        } catch (\Throwable $e) {
            report($e);
            return collect();
        }
    }

    public function deleteBySeeder(string $seeder): bool
    {
        return SeederExecution::where('seeder', $seeder)->delete();
    }

    public function deleteByBatch(int $batch): bool
    {
        return SeederExecution::where('batch', $batch)->delete();
    }

    public function all(): Collection
    {
        try {
            return SeederExecution::all();
        } catch (\Throwable $e) {
            report($e);
            return collect();
        }
    }

    public function truncate(): bool
    {
        try {
            SeederExecution::truncate();
            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }
}
