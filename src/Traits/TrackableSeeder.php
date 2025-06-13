<?php

namespace Riftweb\SuperSeeder\Traits;

use Symfony\Component\Console\Output\ConsoleOutput;
use Throwable;


trait TrackableSeeder
{
    public function run(): void
    {
        if (!$this->shouldRun()) {
            return;
        }

        $this->up();

        $this->markAsRun();
    }

    public function shouldRun(): bool
    {
        $executor = app('superseeder.executor');
        $service = app('superseeder.service');
        $bypass = config('superseeder.bypass') || $executor->isForced();

        return $bypass || $service->seederDoesntExists(static::class);
    }

    abstract protected function up(): void;

    public function markAsRun(): void
    {
        try {
            $service = app('superseeder.service');
            $executor = app('superseeder.executor');

            $service->store(static::class, $executor->currentBatch());

            $this->logExecuted();
        } catch (Throwable $e) {
            report($e);
            $this->logError();
            $this->down();
        }
    }

    protected function logExecuted(): void
    {
        if (app()->runningInConsole()) {
            (new ConsoleOutput())->writeln('<info>Seeding:</info> ' . str(static::class)->afterLast('\\'));
        }
    }

    protected function logError(): void
    {
        if (app()->runningInConsole()) {
            (new ConsoleOutput())->writeln('<error>ERROR:</error> ' . str(static::class)->afterLast('\\'));
        }
    }

    abstract public function down(): void;
}
