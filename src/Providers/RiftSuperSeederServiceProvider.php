<?php

namespace Riftweb\SuperSeeder\Providers;

use Illuminate\Support\ServiceProvider;
use Riftweb\SuperSeeder\Console\Commands\DatabaseSeedCommand;
use Riftweb\SuperSeeder\Console\Commands\DatabaseSeedStatusCommand;
use Riftweb\SuperSeeder\Console\Commands\TrackableSeederMakeCommand;
use Riftweb\SuperSeeder\Repositories\SeederExecutionRepository;
use Riftweb\SuperSeeder\Services\SeederDiscoveryService;
use Riftweb\SuperSeeder\Services\SeederExecutionService;
use Riftweb\SuperSeeder\Services\SeederExecutorService;
use Riftweb\SuperSeeder\Services\SeederRollbackService;
use Riftweb\SuperSeeder\Services\SeederStatusService;

class RiftSuperSeederServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../Config/superseeder.php', 'superseeder'
        );

        // Register commands
        $this->commands([
            DatabaseSeedCommand::class,
            DatabaseSeedStatusCommand::class,
            TrackableSeederMakeCommand::class,
        ]);

        $this->app->singleton(SeederExecutionService::class, function ($app) {
            return new SeederExecutionService(
                $app->make(SeederExecutionRepository::class)
            );
        });
        $this->app->alias(SeederExecutionService::class, 'superseeder.service');

        $this->app->singleton(SeederExecutorService::class, function ($app) {
            return new SeederExecutorService(
                $app->make(SeederExecutionService::class)
            );
        });
        $this->app->alias(SeederExecutorService::class, 'superseeder.executor');

        $this->app->singleton(SeederDiscoveryService::class);
        $this->app->singleton(SeederStatusService::class);
        $this->app->singleton(SeederRollbackService::class);
    }

    public function boot(): void
    {
        // Load migrations (adjust path if needed)
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Publish config file
        $this->publishes([
            __DIR__.'/../Config/superseeder.php' => config_path('superseeder.php'),
        ], 'superseeder-config');

    }
}
