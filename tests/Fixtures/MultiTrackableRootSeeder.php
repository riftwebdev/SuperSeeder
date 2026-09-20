<?php

namespace Tests\Fixtures;

use Illuminate\Database\Seeder;

class MultiTrackableRootSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TrackableTestSeeder::class,
            TaggedTrackableTestSeeder::class,
            ProductionOnlyTrackableTestSeeder::class,
        ]);
    }
}
