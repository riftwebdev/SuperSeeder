<?php

namespace Riftweb\SuperSeeder\Models;

use Illuminate\Database\Eloquent\Model;

class SeederExecution extends Model
{
    protected $fillable = [
        'seeder',
        'batch'
    ];

    public function casts(): array
    {
        return [
            'batch' => 'int',
            'created_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return config("superseeder.table", parent::getTable());
    }
}
