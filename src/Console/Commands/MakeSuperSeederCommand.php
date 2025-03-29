<?php

namespace Riftweb\SuperSeeder\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class MakeSuperSeederCommand extends GeneratorCommand
{
    protected $name = 'make:superseeder';
    protected $description = 'Create a new trackable seeder';
    protected $type = 'Seeder';

    protected function getNameInput()
    {
        // Convert slashes into backslashes for proper namespace
        return str_replace('/', '\\', trim($this->argument('name')));
    }

    protected function getStub()
    {
        $stubPath = __DIR__ . '/../../../stubs/superseeder.stub';

        if (!file_exists($stubPath)) {
            $this->error("❌ Stub file not found: $stubPath");
            exit(1);
        } else {
            $this->info("✅ Stub found at: $stubPath");
        }

        return $stubPath;
    }

    protected function getPath($name)
    {
        // Replace slashes with directory separators and set the full path
        $name = str_replace($this->getDefaultNamespace($this->rootNamespace()), '', $name);
        $name = str_replace('\\', '/', $name);

        $path = base_path("database/seeders/{$name}.php");

        $this->info("📝 Generating seeder at: $path");

        // Ensure the directory exists before writing the file
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true); // Create directories if they don't exist
        }

        return $path;
    }

    protected function replaceClass($stub, $name)
    {
        $className = class_basename($name); // Extracts only the class name
        $namespace = $this->getDefaultNamespace($this->rootNamespace());

        $this->info("Replacing class: DummySeeder → $className");
        $this->info("Replacing namespace: DummyNamespace → $namespace");

        // Perform replacements in stub
        $stub = str_replace('DummySeeder', $className, $stub);
        $stub = str_replace('DummyNamespace', $namespace, $stub);

        return $stub;
    }

    protected function replaceNamespace(&$stub, $name)
    {
        // Convert the input name into a proper namespace format
        $namespace = 'Database\\Seeders'; // The base namespace

        // If there's a subdirectory in the input name, process it
        if (strpos($this->argument('name'), '/') !== false) {
            // Replace '/' with '\\' for the correct namespace
            $namespace .= '\\' . str_replace('/', '\\', dirname($this->argument('name')));
        }

        // Replace the DummyNamespace placeholder in the stub with the correct namespace
        $stub = str_replace('DummyNamespace', $namespace, $stub);

        return $this;
    }


}
