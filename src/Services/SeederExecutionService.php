<?php

namespace Riftweb\SuperSeeder\Services;

use Illuminate\Support\Collection;
use Riftweb\SuperSeeder\Models\SeederExecution;
use Riftweb\SuperSeeder\Repositories\SeederExecutionRepository;

class SeederExecutionService
{
    public function __construct(protected SeederExecutionRepository $seederExecutionRepository) {}

    public function getNextBatch(): int
    {
        return $this->seederExecutionRepository->getNextBatch();
    }

    public function getLatestBatch(): int
    {
        return $this->seederExecutionRepository->getLatestBatch();
    }

    public function truncate(): bool
    {
        return $this->seederExecutionRepository->truncate();
    }

    public function seederDoesntExists(string $seeder): bool
    {
        return $this->seederExecutionRepository->seederDoesntExists($seeder);
    }

    public function store(string $seeder, int $batch): ?SeederExecution
    {
        return $this->seederExecutionRepository->store([
            'seeder' => $seeder,
            'batch' => $batch,
        ]);
    }

    public function getByBatch(int $batch): Collection
    {
        return $this->seederExecutionRepository->getByBatch($batch);
    }

    public function deleteBySeeder(string $seeder): bool
    {
        return $this->seederExecutionRepository->deleteBySeeder($seeder);
    }
}
