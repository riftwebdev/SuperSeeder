<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class SuperSeederTestRecord extends Model
{
    protected $table = 'superseeder_test_records';

    public $timestamps = false;

    protected $guarded = [];
}
