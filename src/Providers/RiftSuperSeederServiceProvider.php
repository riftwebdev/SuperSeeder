<?php

namespace Riftweb\SuperSeeder\Providers;

use Illuminate\Support\ServiceProvider;
use Riftweb\SuperSeeder\Console\Commands\MakeSuperSeederCommand;
use Riftweb\SuperSeeder\Repositories\SeederExecutionRepository;
use Riftweb\SuperSeeder\Services\SeederExecutionService;
use Riftweb\SuperSeeder\Console\Commands\SuperSeedClearCommand;
use Riftweb\SuperSeeder\Console\Commands\SuperSeedFreshCommand;
use Riftweb\SuperSeeder\Console\Commands\SuperSeedCommand;
use Riftweb\SuperSeeder\Console\Commands\SuperSeedRollbackCommand;
use Riftweb\SuperSeeder\Models\SeederExecution;
use Riftweb\SuperSeeder\Services\SeederExecutorService;

class RiftSuperSeederServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../Config/superseeder.php', 'superseeder'
        );

        // Register commands
        $this->commands([
            SuperSeedCommand::class,
            SuperSeedRollbackCommand::class,
            SuperSeedClearCommand::class,
            SuperSeedFreshCommand::class,
            MakeSuperSeederCommand::class,
        ]);

        $this->app->singleton('superseeder.service', function ($app) {
            // You can pass any dependencies to the SeederExecutor constructor if necessary
            return new SeederExecutionService(
                $app->make(SeederExecutionRepository::class)
            );
        });

        $this->app->singleton('superseeder.executor', function ($app) {
            // You can pass any dependencies to the SeederExecutor constructor if necessary
            return new SeederExecutorService(
                $app->make('superseeder.service')
            );
        });
    }
    public function boot()
    {
        // Load migrations (adjust path if needed)
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Publish config file
        $this->publishes([
            __DIR__.'/../Config/superseeder.php' => config_path('superseeder.php'),
        ], 'superseeder-config');

        // Bind the model (optional, for dependency injection)
        $this->app->bind('seeder-execution-model', function () {
            return new SeederExecution();
        });
    }
}
