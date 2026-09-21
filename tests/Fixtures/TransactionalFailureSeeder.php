<?php

namespace Tests\Fixtures;

use Illuminate\Database\Seeder;
use RuntimeException;
use Riftweb\SuperSeeder\Traits\Trackable;

class TransactionalFailureSeeder extends Seeder
{
    use Trackable;

    protected function up(): void
    {
        $this->track(SuperSeederTestRecord::create([
            'name' => 'transaction-failed',
        ]));

        throw new RuntimeException('Seeder failed during execution.');
    }

    public function down(): void
    {
        $this->pruneModels(
            SuperSeederTestRecord::class,
            SuperSeederTestRecord::query()->where('name', 'transaction-failed')->get(),
            false,
        );
    }
}
