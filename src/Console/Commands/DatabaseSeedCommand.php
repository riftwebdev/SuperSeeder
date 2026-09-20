<?php

namespace Riftweb\SuperSeeder\Console\Commands;

use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Console\Seeds\SeedCommand;
use ReflectionClass;
use Riftweb\SuperSeeder\Exceptions\RollbackBlockedException;
use Riftweb\SuperSeeder\Services\SeederExecutionService;
use Riftweb\SuperSeeder\Services\SeederStatusService;
use Riftweb\SuperSeeder\Services\SeederExecutorService;
use Riftweb\SuperSeeder\Services\SeederRollbackService;

class DatabaseSeedCommand extends SeedCommand
{
    protected $signature = 'db:seed
                    {class? : The class name of the root seeder}
                    {--class=Database\\Seeders\\DatabaseSeeder : The class name of the root seeder}
                    {--database= : The database connection to seed}
                    {--force : Force the operation to run when in production}
                    {--rerun : Bypass SuperSeeder tracking for this run}
                    {--rollback : Rollback the latest SuperSeeder batch}
                    {--fresh : Clear SuperSeeder tracking and rerun all trackable seeders}
                    {--clear : Clear all SuperSeeder tracking records}
                    {--dry-run : Show rollback effects without making changes}
                    {--cascade : Allow rollback when dependent records exist}
                    {--status : Show SuperSeeder execution status}
                    {--tag=* : Only run or rollback seeders with the given tag(s)}';

    public function __construct(
        Resolver $resolver,
        protected SeederExecutionService $seederExecutionService,
        protected SeederExecutorService $seederExecutorService,
        protected SeederRollbackService $seederRollbackService,
        protected SeederStatusService $seederStatusService,
    ) {
        parent::__construct($resolver);
    }

    public function handle(): int
    {
        $operations = collect(['rollback', 'fresh', 'clear', 'status'])
            ->filter(fn (string $operation): bool => $this->option($operation));

        if ($operations->count() > 1) {
            $this->error(sprintf('The --%s and --%s options cannot be used together.', $operations->first(), $operations->last()));

            return self::FAILURE;
        }

        if ($this->option('rerun') && $operations->isNotEmpty()) {
            $this->error('The --rerun option can only be used while seeding.');

            return self::FAILURE;
        }

        if ($this->option('status')) {
            return $this->showStatus();
        }

        if ($this->option('fresh')) {
            return $this->runFreshSeeders();
        }

        if ($this->option('clear')) {
            return $this->runClearTracking();
        }

        if (! $this->option('rollback')) {
            $this->seederExecutorService->setForce($this->option('rerun'));
            $this->seederExecutorService->setTags($this->tags());

            return parent::handle();
        }

        if (app()->isProduction() && ! config('superseeder.rollback.production_enabled')) {
            $this->error('SuperSeeder rollbacks are disabled in production. Set superseeder.rollback.production_enabled to true to allow them.');

            return self::FAILURE;
        }

        if (! app()->isLocal() && ! $this->option('force')) {
            $this->error('Rollback requires --force outside the local environment.');

            return self::FAILURE;
        }

        $batch = $this->seederExecutionService->getLatestBatch();

        if (! $batch) {
            $this->info('No seeders to rollback.');

            return self::SUCCESS;
        }

        $seeders = $this->seederExecutionService->getByBatch($batch)
            ->filter(fn ($execution): bool => $this->matchesRequestedTags($execution->seeder))
            ->values();

        if ($seeders->isEmpty()) {
            $this->info('No seeders matched the requested rollback scope.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Rolling back batch #%d (%d seeder(s))', $batch, $seeders->count()));

        try {
            $rollbackResult = $this->seederRollbackService->rollbackBatch(
                $seeders,
                $this->option('dry-run'),
                $this->option('cascade'),
            );
        } catch (RollbackBlockedException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach ($rollbackResult['warnings'] as $warning) {
            $this->warn($warning);
        }

        foreach ($rollbackResult['seeders'] as $seeder) {
            $this->line(sprintf(
                '%s: %s',
                $this->option('dry-run') ? 'Would rollback' : 'Rollback',
                class_basename($seeder),
            ));
        }

        $this->info($this->option('dry-run') ? 'Dry run completed. No changes were made.' : 'Rollback completed.');

        return self::SUCCESS;
    }

    protected function showStatus(): int
    {
        $class = $this->argument('class');

        if (! $class && $this->input->hasParameterOption('--class')) {
            $class = $this->option('class');
        }

        $rows = $this->seederStatusService->rows($class);

        if ($rows === []) {
            $this->info('No trackable seeders found.');

            return self::SUCCESS;
        }

        $this->table(['Seeder', 'Status', 'Batch', 'Time', 'Executed At'], $rows);

        return self::SUCCESS;
    }

    protected function runFreshSeeders(): int
    {
        if (! $this->confirmAndClearTracking('This will clear all SuperSeeder tracking records and rerun every trackable seeder. Continue?')) {
            return self::FAILURE;
        }

        $this->seederExecutorService->setForce(false);
        $this->seederExecutorService->setTags($this->tags());

        return parent::handle();
    }

    protected function runClearTracking(): int
    {
        if (! $this->confirmAndClearTracking('This will clear all SuperSeeder tracking records. Continue?')) {
            return self::FAILURE;
        }

        $this->info('SuperSeeder tracking records cleared.');

        return self::SUCCESS;
    }

    protected function confirmAndClearTracking(string $question): bool
    {
        if (! app()->isLocal() && ! $this->option('force')) {
            $this->error('Clearing SuperSeeder tracking requires --force outside the local environment.');

            return false;
        }

        if (! $this->confirm($question)) {
            $this->info('Seeder tracking was not cleared. No changes were made.');

            return false;
        }

        if (! $this->seederExecutorService->clear()) {
            $this->error('Unable to clear SuperSeeder tracking records.');

            return false;
        }

        return true;
    }

    /**
     * @return list<string>
     */
    protected function tags(): array
    {
        return array_values(array_filter((array) $this->option('tag'), fn (?string $tag): bool => filled($tag)));
    }

    protected function matchesRequestedTags(string $seeder): bool
    {
        $tags = $this->tags();

        if ($tags === []) {
            return true;
        }

        $seederTags = [];

        if (class_exists($seeder)) {
            $seederTags = (array) ((new ReflectionClass($seeder))->getDefaultProperties()['tags'] ?? []);
        }

        return array_intersect($tags, $seederTags) !== [];
    }
}
