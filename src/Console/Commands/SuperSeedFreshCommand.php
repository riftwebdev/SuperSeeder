<?php

namespace Riftweb\SuperSeeder\Console\Commands;

use Illuminate\Console\Command;

class SuperSeedFreshCommand extends Command
{
    protected $name = 'superseed:fresh';
    protected $description = 'Clear tracking and rerun all seeders';

    public function handle()
    {
        $this->call('superseed:clear');
        $this->call('superseed');
    }
}