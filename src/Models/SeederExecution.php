<?php
namespace Riftweb\SuperSeeder\Models;

use Illuminate\Database\Eloquent\Model;

class SeederExecution extends Model
{
    protected string $table = 'seeder_executions';

    protected array $fillable = ['seeder', 'batch'];

    public function casts(): array
    {
        return [
            'batch' => 'int'
        ];
    }
}