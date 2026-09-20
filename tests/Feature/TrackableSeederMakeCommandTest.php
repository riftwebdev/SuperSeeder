<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

function seederDirectory(): string
{
    $app = app();

    return is_dir($app->databasePath('seeds'))
        ? $app->databasePath('seeds')
        : $app->databasePath('seeders');
}

afterEach(function (): void {
    Carbon::setTestNow();

    File::delete(File::glob(seederDirectory().'/*UserSeeder.php'));
    File::delete(File::glob(seederDirectory().'/*RoleSeeder.php'));
});

it('creates timestamped trackable seeders by default', function (): void {
    Carbon::setTestNow('2026-09-01 14:45:00');

    $this->artisan('make:seeder', [
        'name' => 'UserSeeder',
        '--trackable' => true,
    ])->assertExitCode(0);

    $path = seederDirectory().'/20260901144500UserSeeder.php';

    expect(File::exists($path))->toBeTrue()
        ->and(File::get($path))->toContain('class UserSeeder extends Seeder')
        ->and(File::get($path))->toContain('use Trackable;');
});

it('creates non-timestamped trackable seeders when disabled in config', function (): void {
    config()->set('superseeder.use_timestamped_seeders', false);
    Carbon::setTestNow('2026-09-01 14:45:00');

    $this->artisan('make:seeder', [
        'name' => 'RoleSeeder',
        '--trackable' => true,
    ])->assertExitCode(0);

    expect(File::exists(seederDirectory().'/RoleSeeder.php'))->toBeTrue()
        ->and(File::exists(seederDirectory().'/20260901144500RoleSeeder.php'))->toBeFalse();
});
