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

    protected function getPath($name): string
    {
        $path = parent::getPath($name);

        if (! $this->shouldUseTimestampedSeederPaths()) {
            return $path;
        }

        return dirname($path).'/'.$this->getSeederTimestampPrefix().basename($path);
    }

    protected function alreadyExists($rawName): bool
    {
        if (! $this->shouldUseTimestampedSeederPaths()) {
            return parent::alreadyExists($rawName);
        }

        $path = parent::getPath($this->qualifyClass($rawName));

        return $this->files->exists($path)
            || $this->files->glob(dirname($path).'/*'.basename($path)) !== [];
    }

    protected function shouldUseTimestampedSeederPaths(): bool
    {
        return $this->option('trackable')
            && (bool) config('superseeder.use_timestamped_seeders', true);
    }

    protected function getSeederTimestampPrefix(): string
    {
        return now()->format('YmdHis');
    }
}
