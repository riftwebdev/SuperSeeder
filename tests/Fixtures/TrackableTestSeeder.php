<?php

namespace Tests\Fixtures;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Riftweb\SuperSeeder\Traits\Trackable;

class TrackableTestSeeder extends Seeder
{
    use Trackable;

    protected function up(): void
    {
        DB::table('superseeder_test_records')->insert([
            'name' => 'seeded',
        ]);
    }

    public function down(): void
    {
        DB::table('superseeder_test_records')
            ->where('name', 'seeded')
            ->delete();
    }

    public function seededRecords(): array
    {
        return [
            'superseeder_test_records' => [
                'id' => [1],
            ],
        ];
    }
}
