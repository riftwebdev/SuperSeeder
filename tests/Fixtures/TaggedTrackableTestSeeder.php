<?php

namespace Tests\Fixtures;

use Illuminate\Database\Seeder;
use Riftweb\SuperSeeder\Traits\Trackable;

class TaggedTrackableTestSeeder extends Seeder
{
    use Trackable;

    protected array $tags = ['roles'];

    protected function up(): void
    {
        $this->track(SuperSeederTestRecord::create([
            'name' => 'role-seeded',
        ]));
    }

    public function down(): void
    {
        $this->pruneModels(
            SuperSeederTestRecord::class,
            SuperSeederTestRecord::query()->where('name', 'role-seeded')->get(),
            false,
        );
    }
}
