<?php

namespace Riftweb\SuperSeeder\Console\Commands;

use Illuminate\Database\Console\Seeds\SeederMakeCommand;

class TrackableSeederMakeCommand extends SeederMakeCommand
{
    protected $signature = 'make:seeder
                    {name : The name of the seeder}
                    {--trackable : Generate a SuperSeeder trackable seeder}';

    protected function getStub(): string
    {
        if ($this->option('trackable')) {
            return __DIR__.'/../../../stubs/superseeder.stub';
        }

        return parent::getStub();
    }
}
