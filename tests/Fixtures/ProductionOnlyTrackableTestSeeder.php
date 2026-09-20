<?php

namespace Tests\Fixtures;

use Illuminate\Database\Seeder;
use Riftweb\SuperSeeder\Traits\Trackable;

class ProductionOnlyTrackableTestSeeder extends Seeder
{
    use Trackable;

    protected array $environments = ['production'];

    protected function up(): void
    {
        $this->track(SuperSeederTestRecord::create([
            'name' => 'production-only',
        ]));
    }

    public function down(): void
    {
        $this->pruneModels(
            SuperSeederTestRecord::class,
            SuperSeederTestRecord::query()->where('name', 'production-only')->get(),
            false,
        );
    }
}
