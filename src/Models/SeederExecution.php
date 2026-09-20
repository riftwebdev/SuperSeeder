<?php

namespace Riftweb\SuperSeeder\Models;

use Illuminate\Database\Eloquent\Model;

class SeederExecution extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'seeder',
        'batch',
        'status',
        'execution_time_ms',
        'tracked_records',
        'record_hash',
        'seeder_hash',
    ];

    public function casts(): array
    {
        return [
            'batch' => 'int',
            'execution_time_ms' => 'int',
            'tracked_records' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return config('superseeder.table', parent::getTable());
    }
}
