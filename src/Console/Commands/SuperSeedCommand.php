<?php

namespace Riftweb\SuperSeeder\Console\Commands;

use Illuminate\Console\Command;
use Riftweb\SuperSeeder\Services\SeederExecutor;

class SuperSeedCommand extends Command
{
    protected $name = 'superseed';
    protected $description = 'Run all pending seeders';
    protected $signature = 'superseed {--force : Bypass tracking checks}';

    public function handle(SeederExecutor $executor)
    {
        $executor->setForce($this->option('force'));

        $pendingSeeders = $executor->getPendingSeeders();

        if (empty($pendingSeeders)) {
            $this->info('No seeders to run.');
            return;
        }

        $this->info('Running ' . count($pendingSeeders) . ' seeder(s)...');

        try {
            $executed = $executor->runSeeders($pendingSeeders);
            $this->info('Successfully ran seeders: ');
            $this->table(['Seeders'], array_map(fn($s) => [$s], $executed));
        } catch (\Throwable $e) {
            $this->error("Seeder failed: " . $e->getMessage());
        }
    }
}