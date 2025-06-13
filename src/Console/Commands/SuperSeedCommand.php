<?php

namespace Riftweb\SuperSeeder\Console\Commands;

use Illuminate\Console\Command;
use Riftweb\SuperSeeder\Services\SeederExecutionService;
use Riftweb\SuperSeeder\Services\SeederExecutorService;
use Throwable;

class SuperSeedCommand extends Command
{
    protected $name = 'superseed';
    protected $description = 'Run all pending seeders';
    protected $signature = 'superseed {--force : Bypass tracking checks}';

    public function handle(
        SeederExecutionService $seederExecutionService,
        SeederExecutorService $seederExecutorService
    ): void
    {
        $seederExecutorService->setForce($this->option('force'));

        $pendingSeeders = $seederExecutorService->getPendingSeeders();

        if (empty($pendingSeeders)) {
            $this->info('No seeders to run.');
            return;
        }

        $this->displayPendingCount($pendingSeeders);
        $this->executeSeeders($seederExecutionService, $seederExecutorService, $pendingSeeders);
    }

    protected function displayPendingCount(array $pendingSeeders): void
    {
        $this->info(
            sprintf(
                'Found %d pending seeder%s...',
                count($pendingSeeders),
                count($pendingSeeders) === 1 ? '' : 's'
            )
        );
    }

    protected function executeSeeders(
        SeederExecutionService $seederExecutionService,
        SeederExecutorService $executor,
        array                 $pendingSeeders
    ): void
    {
        $this->info('Running seeders:');

        try {
            $executor->runSeeders($pendingSeeders);

            $executed = $seederExecutionService->mapBatchForConsoleTable($executor->currentBatch());

            $this->displaySuccessMessage($executed);
        } catch (Throwable $e) {
            $this->handleExecutionError($e);
        }
    }

    protected function displaySuccessMessage(array $executedSeeders): void
    {
        if (empty($executedSeeders)) {
            $this->info("\nNo seeders were executed.");
            return;
        }

        $this->info("\nSuccessfully ran seeders:");

        $this->table(
            ['Executed Seeders'],
            collect($executedSeeders)->map(fn ($seeder) => [$seeder])->toArray()
        );
    }

    protected function handleExecutionError(Throwable $e): void
    {
        $this->error("Seeder failed: " . $e->getMessage());
        report($e);
    }
}
