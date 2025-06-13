<?php

namespace Riftweb\SuperSeeder\Repositories;

use Illuminate\Support\Collection;
use Riftweb\SuperSeeder\Models\SeederExecution;
use Throwable;

class SeederExecutionRepository
{
    public function store(array $data): ?SeederExecution
    {
        try {
            return SeederExecution::create($data);
        } catch (Throwable $e) {
            report($e);
            return null;
        }
    }

    public function getNextBatch(): int
    {
        try {
            return $this->getLatestBatch() + 1;
        } catch (Throwable $e) {
            report($e);
            return 1;
        }
    }

    public function getLatestBatch(): int
    {
        try {
            return SeederExecution::max('batch') ?? 0;
        } catch (Throwable $e) {
            report($e);
            return 0;
        }
    }

    public function seederExists(string $seeder): bool
    {
        try {
            return SeederExecution::where('seeder', $seeder)->exists();
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }

    public function seederDoesntExists(string $seeder): bool
    {
        try {
            return SeederExecution::where('seeder', $seeder)->doesntExist();
        } catch (Throwable $e) {
            report($e);
            return true;
        }
    }

    public function findBySeeder(string $seeder): ?SeederExecution
    {
        try {
            return SeederExecution::where('seeder', $seeder)
                ->first();
        } catch (Throwable $e) {
            report($e);
            return null;
        }
    }

    public function getByBatch(int $batch): Collection
    {
        try {
            return SeederExecution::where('batch', $batch)
                ->orderByDesc('id')
                ->get();
        } catch (Throwable $e) {
            report($e);
            return collect();
        }
    }

    public function deleteBySeeder(string $seeder): bool
    {
        try {
            return SeederExecution::where('seeder', $seeder)
                ->delete();
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }

    public function deleteByBatch(int $batch): bool
    {
        try {
            return SeederExecution::where('batch', $batch)
                ->delete();
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }

    public function all(): Collection
    {
        try {
            return SeederExecution::all();
        } catch (Throwable $e) {
            report($e);
            return collect();
        }
    }

    public function truncate(): bool
    {
        try {
            SeederExecution::truncate();
            return true;
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }
}
