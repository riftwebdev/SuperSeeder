<?php

namespace Riftweb\SuperSeeder\Console\Commands;

use Illuminate\Console\Command;
use Riftweb\SuperSeeder\Services\SeederExecutionService;
use Riftweb\SuperSeeder\Services\SeederExecutorService;
use Throwable;

class SuperSeedRollbackCommand extends Command
{
    protected $name = 'superseed:rollback';
    protected $description = 'Rollback the last batch of seeders';

    public function handle(
        SeederExecutionService $seederExecutionService,
        SeederExecutorService  $executor
    ): void
    {
        $batch = $seederExecutionService->getLatestBatch();

        if (!$batch) {
            $this->info('No seeders to rollback.');
            return;
        }

        $seeders = $seederExecutionService->getByBatch($batch)
            ->pluck('seeder')
            ->toArray();

        $this->info("Rolling back batch #$batch (" . count($seeders) . " seeder(s))");

        try {
            $executor->rollbackBatch($seeders);
            $this->info("\nRollback completed!");
        } catch (Throwable $e) {
            $this->error("Rollback failed: " . $e->getMessage());
        }
    }
}