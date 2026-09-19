<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Riftweb\SuperSeeder\Models\SeederExecution;
use Tests\Fixtures\TrackableTestSeeder;

function runTrackableSeeder(): void
{
    test()->artisan('db:seed', [
        '--class' => TrackableTestSeeder::class,
    ])->assertExitCode(0);
}

it('tracks a seeder and skips it on later native seed runs', function (): void {
    runTrackableSeeder();
    runTrackableSeeder();

    expect(DB::table('superseeder_test_records')->count())->toBe(1)
        ->and(SeederExecution::where('seeder', TrackableTestSeeder::class)->count())->toBe(1);
});

it('reruns tracked seeders when requested', function (): void {
    runTrackableSeeder();

    $this->artisan('db:seed', [
        '--class' => TrackableTestSeeder::class,
        '--rerun' => true,
    ])->assertExitCode(0);

    expect(DB::table('superseeder_test_records')->count())->toBe(2)
        ->and(SeederExecution::where('seeder', TrackableTestSeeder::class)->count())->toBe(2);
});

it('clears tracking after confirmation', function (): void {
    runTrackableSeeder();

    $this->artisan('db:seed', [
        '--clear' => true,
        '--force' => true,
    ])
        ->expectsConfirmation('This will clear all SuperSeeder tracking records. Continue?', 'yes')
        ->assertExitCode(0);

    expect(SeederExecution::query()->count())->toBe(0)
        ->and(DB::table('superseeder_test_records')->count())->toBe(1);
});

it('freshly clears tracking and reruns seeders after confirmation', function (): void {
    runTrackableSeeder();

    $this->artisan('db:seed', [
        '--class' => TrackableTestSeeder::class,
        '--fresh' => true,
        '--force' => true,
    ])->expectsConfirmation('This will clear all SuperSeeder tracking records and rerun every trackable seeder. Continue?', 'yes')
        ->assertExitCode(0);

    expect(DB::table('superseeder_test_records')->count())->toBe(2)
        ->and(SeederExecution::where('seeder', TrackableTestSeeder::class)->count())->toBe(1);
});

it('previews a rollback without modifying records or tracking', function (): void {
    runTrackableSeeder();

    $this->artisan('db:seed', [
        '--rollback' => true,
        '--dry-run' => true,
        '--force' => true,
    ])->assertExitCode(0);

    expect(DB::table('superseeder_test_records')->count())->toBe(1)
        ->and(SeederExecution::where('seeder', TrackableTestSeeder::class)->count())->toBe(1);
});

it('blocks rollback when a foreign key references seeded records', function (): void {
    runTrackableSeeder();

    Schema::create('superseeder_test_dependencies', function ($table): void {
        $table->id();
        $table->foreignId('superseeder_test_record_id')
            ->constrained('superseeder_test_records')
            ->cascadeOnDelete();
    });

    DB::table('superseeder_test_dependencies')->insert([
        'superseeder_test_record_id' => 1,
    ]);

    $this->artisan('db:seed', [
        '--rollback' => true,
        '--force' => true,
    ])->expectsOutputToContain('Cannot rollback TrackableTestSeeder')
        ->assertExitCode(1);

    $this->artisan('db:seed', [
        '--rollback' => true,
        '--cascade' => true,
        '--force' => true,
    ])->assertExitCode(0);

    expect(DB::table('superseeder_test_records')->count())->toBe(0)
        ->and(DB::table('superseeder_test_dependencies')->count())->toBe(0)
        ->and(SeederExecution::query()->count())->toBe(0);
});
