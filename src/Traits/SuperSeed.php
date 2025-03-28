<?php

namespace Riftweb\SuperSeeder\Traits;

use Riftweb\SuperSeeder\Models\SeederExecution;

trait TrackableSeed
{
    public function shouldRun(): bool
    {
        $executor = app('seeder.executor');
        $bypass = config('superseeder.bypass') || $executor->isForced();

        return $bypass || !SeederExecution::where('seeder', static::class)->exists();
    }

    public function markAsRun(): void
    {
        SeederExecution::create([
            'seeder' => static::class,
            'batch' => app('seeder.executor')->getCurrentBatch(),
        ]);
    }

    /**
     * Define rollback logic in your seeder
     */
    abstract public function rollback(): void;
}