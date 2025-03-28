<?php

namespace Riftweb\SuperSeeder\Console\Commands;

use Illuminate\Console\Command;
use Riftweb\SuperSeeder\Services\SeederExecutor;

class SuperSeedRollbackCommand extends Command
{
    protected $name = 'superseed:rollback';
    protected $description = 'Rollback the last batch of seeders';

    public function handle(SeederExecutor $executor)
    {
        $batch = $executor->getLastBatch();

        if (!$batch) {
            $this->info('No seeders to rollback.');
            return;
        }

        $seeders = SeederExecution::where('batch', $batch)
            ->orderByDesc('id')
            ->pluck('seeder')
            ->toArray();

        $this->info("Rolling back batch #{$batch} (" . count($seeders) . " seeder(s))");

        try {
            $executor->rollbackBatch($seeders);
            $this->info("\nRollback completed!");
        } catch (\Throwable $e) {
            $this->error("Rollback failed: " . $e->getMessage());
        }
    }
}