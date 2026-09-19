<?php

namespace Tests;

use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Riftweb\SuperSeeder\Providers\RiftSuperSeederServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            RiftSuperSeederServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--force' => true])->run();

        Schema::create('superseeder_test_records', function ($table): void {
            $table->id();
            $table->string('name');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('superseeder_test_dependencies');
        Schema::dropIfExists('superseeder_test_records');

        parent::tearDown();
    }
}
