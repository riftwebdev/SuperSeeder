<?php
namespace Riftweb\SuperSeeder\Models;

use Illuminate\Database\Eloquent\Model;

class SeederExecution extends Model
{
    protected $table = 'seeder_executions';

    protected $fillable = ['seeder', 'batch'];

    public function casts(): array
    {
        return [
            'batch' => 'int'
        ];
    }

    public function getTable(): string
    {
        return config("superseeder.table", parent::getTable());
    }
}
