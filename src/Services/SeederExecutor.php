<?php

namespace Riftweb\SuperSeeder\Services;

use Illuminate\Support\Facades\File;
use Riftweb\SuperSeeder\Models\SeederExecution;

class SeederExecutor
{
    protected static $currentBatch = null;
    protected $force = false;

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
        $allSeeders = $this->getAllSeeders();

        if ($this->force || config('superseeder.bypass')) {
            return $allSeeders;
        }

        $tracked = SeederExecution::pluck('seeder')->toArray();
        return array_diff($allSeeders, $tracked);
    }

    public function getAllSeeders()
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

    public function runSeeders(array $seeders)
    {
        $this->getNextBatchNumber();
        $executed = [];

        foreach ($seeders as $seeder) {
            try {
                $instance = app($seeder);
                $instance->run();

                SeederExecution::create([
                    'seeder' => $seeder,
                    'batch' => self::$currentBatch,
                ]);

                $executed[] = $seeder;
            } catch (\Throwable $e) {
                // Rollback executed seeders in this batch on failure
                $this->rollbackBatch($executed);
                throw $e;
            }
        }

        return $executed;
    }

    protected function getNextBatchNumber()
    {
        if (self::$currentBatch === null) {
            self::$currentBatch = SeederExecution::max('batch') + 1;
        }
        return self::$currentBatch;
    }

    public function rollbackBatch(array $seeders): void
    {
        foreach ($seeders as $seeder) {
            $instance = app($seeder);

            // Run the seeder's rollback logic
            $instance->rollback();

            // Delete the tracking record
            SeederExecution::where('seeder', $seeder)->delete();

            $this->info("Rolled back: {$seeder}");
        }
    }

    public function getLastBatch()
    {
        return SeederExecution::max('batch');
    }
}