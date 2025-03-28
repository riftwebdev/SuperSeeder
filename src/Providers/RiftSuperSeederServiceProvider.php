<?php

namespace Riftweb\SuperSeeder\Providers;

use Illuminate\Support\ServiceProvider;
use Riftweb\SuperSeeder\Console\Commands\MakeSuperSeederCommand;
use Riftweb\SuperSeeder\Console\Commands\SuperSeedClearCommand;
use Riftweb\SuperSeeder\Console\Commands\SuperSeedFreshCommand;
use Riftweb\SuperSeeder\Console\Commands\SuperSeedCommand;
use Riftweb\SuperSeeder\Console\Commands\SuperSeedRollbackCommand;
use Riftweb\SuperSeeder\Models\SeederExecution;

class RiftSuperSeederServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/superseeder.php', 'superseeder'
        );

        // Register commands
        $this->commands([
            SuperSeedCommand::class,
            SuperSeedRollbackCommand::class,
            SuperSeedClearCommand::class,
            SuperSeedFreshCommand::class,
            MakeSuperSeederCommand::class,
        ]);
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