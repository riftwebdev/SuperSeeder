<?php

namespace Riftweb\SuperSeeder\Console\Commands;

use Illuminate\Console\Command;
use Riftweb\SuperSeeder\Services\SeederStatusService;

class DatabaseSeedStatusCommand extends Command
{
    protected $signature = 'db:seed:status
                    {class? : The class name of the seeder to inspect}';

    protected $description = 'Show the execution status for SuperSeeder trackable seeders';

    public function __construct(protected SeederStatusService $seederStatusService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $rows = $this->seederStatusService->rows($this->argument('class'));

        if ($rows === []) {
            $this->info('No trackable seeders found.');

            return self::SUCCESS;
        }

        $this->table(['Seeder', 'Status', 'Batch', 'Time', 'Executed At'], $rows);

        return self::SUCCESS;
    }
}
