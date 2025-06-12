<?php

namespace Riftweb\SuperSeeder\Console\Commands;

use Illuminate\Console\Command;

class SuperSeedFreshCommand extends Command
{
    protected string $name = 'superseed:fresh';
    protected string $description = 'Clear tracking and rerun all seeders';

    public function handle(): void
    {
        $this->call('superseed:clear');
        $this->call('superseed');
    }
}