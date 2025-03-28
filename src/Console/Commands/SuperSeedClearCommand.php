<?php

namespace Riftweb\SuperSeeder\Console\Commands;

use Illuminate\Console\Command;
use Riftweb\SuperSeeder\Models\SeederExecution;
class SuperSeedClearCommand extends Command
{
    protected $name = 'superseed:clear';
    protected $description = 'Clear all seeder tracking records';

    public function handle()
    {
        if ($this->confirm('This will delete all seeder tracking records. Are you sure?')) {
            SeederExecution::truncate();
            $this->info('Seeder tracking cleared!');
        }

        $this->info('Seeder tracking was not cleared. No changes were made.');
    }
}