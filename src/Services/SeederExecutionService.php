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

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function store(string $seeder, int $batch, array $attributes = []): ?SeederExecution
    {
        return $this->seederExecutionRepository->store([
            'seeder' => $seeder,
            'batch' => $batch,
            ...$attributes,
        ]);
    }

    public function getByBatch(int $batch): Collection
    {
        return $this->seederExecutionRepository->getByBatch($batch);
    }

    public function getLatestExecutionForSeeder(string $seeder): ?SeederExecution
    {
        return $this->seederExecutionRepository->getLatestExecutionForSeeder($seeder);
    }

    public function getLatestExecutions(): Collection
    {
        return $this->seederExecutionRepository->getLatestExecutions();
    }

    public function getTrackedSeederClasses(): Collection
    {
        return $this->seederExecutionRepository->getTrackedSeederClasses();
    }

    public function deleteExecution(int $id): bool
    {
        return $this->seederExecutionRepository->deleteExecution($id);
    }
}
