<?php

namespace Riftweb\SuperSeeder\Console\Commands;

use Illuminate\Console\Command;
use Riftweb\SuperSeeder\Models\SeederExecution;
use Riftweb\SuperSeeder\Services\SeederExecutionService;
use Riftweb\SuperSeeder\Services\SeederExecutorService;

class SuperSeedClearCommand extends Command
{
    protected $name = 'superseed:clear';
    protected $description = 'Clear all seeder tracking records';

    public function handle(SeederExecutorService $seederExecutorService): void
    {
        if ($this->confirm('This will delete all seeder tracking records. Are you sure?')) {
            if ($seederExecutorService->clear()) {
                $this->info('Seeder tracking cleared!');
                return;
            }
        }

        $this->info('Seeder tracking was not cleared. No changes were made.');
    }
}
