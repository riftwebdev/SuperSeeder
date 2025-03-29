<?php

namespace Riftweb\SuperSeeder\Services;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Laravel\Prompts\Output\ConsoleOutput;

class SeederExecutorService
{
    public function __construct(
        protected SeederExecutionService $seederExecutionService
    )
    {
    }

    protected static ?int $currentBatch = null;
    protected bool $force = false;

    public function setForce(bool $force): self
    {
        $this->force = $force;
        return $this;
    }

    public function isForced(): bool
    {
        return $this->force;
    }
    public function getPendingSeeders()
    {
        $allSeeders = $this->getAllSeederClasses();

        if ($this->force || config('superseeder.bypass')) {
            return $allSeeders;
        }

        $tracked = $this->seederExecutionService
            ->all()
            ->pluck('seeder')
            ->toArray();
        
        return array_diff($allSeeders, $tracked);
    }

    public function getAllSeederClasses()
    {
        $seedersPath = database_path('seeders');
        $files = File::glob("{$seedersPath}/*.php");

        return collect($files)->map(function ($file) {
            $className = str_replace(
                ['/', '.php'],
                ['\\', ''],
                'Database\\Seeders\\' . basename($file)
            );

            return class_exists($className) ? $className : null;
        })->filter()->toArray();
    }

    public function runSeeders(array $seeders): void
    {
        $batch = $this->getNextBatch();

        foreach ($seeders as $seeder) {
            try {
                $instance = app($seeder);
                $instance->run();
            } catch (\Throwable $e) {
                // Rollback executed seeders in this batch on failure
                $this->rollbackBatch($batch);
                throw $e;
            }
        }
    }

    protected function getNextBatch()
    {
        if (is_null(self::$currentBatch)) {
            self::$currentBatch = $this->seederExecutionService->getNextBatch();
        }

        return self::$currentBatch;
    }

    public function rollbackBatch(array $seeders): void
    {
        $appInConsole = app()->runningInConsole();

        try {
            foreach ($seeders as $seeder) {
                if (
                    $appInConsole &&
                    $this->seederExecutionService->seederDoesntExists($seeder)
                ) {
                    (new ConsoleOutput())->writeln('<info>Skipped:</info> ' . str($seeder)->afterLast('\\'));
                    continue;
                }

                $instance = app($seeder);

                // Run the seeder's rollback logic
                $instance->down();


                // Delete the tracking record
                $this->seederExecutionService->deleteBySeeder($seeder);

                if ($appInConsole) {
                    (new ConsoleOutput())->writeln('<info>Rollback:</info> ' . str($seeder)->afterLast('\\'));
                }
            }
        } catch (\Throwable $e) {
            report($e);

            if ($appInConsole) {
                (new ConsoleOutput())->writeln('<error>ERROR:</error> ' . str($seeder)->afterLast('\\'));
            }
        }
    }

    public function clear(): bool
    {
        return $this->seederExecutionService->truncate();
    }

    public function currentBatch(): int
    {
        if (is_null(self::$currentBatch)) {
            self::$currentBatch = $this->seederExecutionService->getNextBatch();
        }

        return self::$currentBatch;
    }
}
