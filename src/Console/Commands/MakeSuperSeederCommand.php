<?php

namespace Riftweb\SuperSeeder\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class MakeSuperSeederCommand extends GeneratorCommand
{
    protected $name = 'make:superseeder';
    protected $description = 'Create a new trackable seeder';
    protected $type = 'Seeder';

    protected function getStub()
    {
        return __DIR__.'/../../../stubs/superseeder.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return config('superseeder.namespace', $rootNamespace . '\Database\Seeders');
    }

    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the seeder'],
        ];
    }

    protected function replaceClass($stub, $name)
    {
        $stub = parent::replaceClass($stub, $name);
        return str_replace('DummySeeder', $this->argument('name'), $stub);
    }

    // NEW: Handle namespace replacement
    protected function replaceNamespace(&$stub, $name)
    {
        $namespace = $this->getDefaultNamespace($this->rootNamespace());
        $stub = str_replace('DummyNamespace', $namespace, $stub);

        return $this;
    }
}