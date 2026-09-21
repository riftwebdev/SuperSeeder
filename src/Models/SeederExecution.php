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
        'tags',
        'record_hash',
        'record_hash_requires_unique_columns',
        'seeder_hash',
    ];

    public function casts(): array
    {
        return [
            'batch' => 'int',
            'execution_time_ms' => 'int',
            'tracked_records' => 'array',
            'tags' => 'array',
            'record_hash_requires_unique_columns' => 'bool',
            'created_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return config('superseeder.table', parent::getTable());
    }
}
