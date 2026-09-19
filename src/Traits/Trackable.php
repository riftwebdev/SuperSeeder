<?php

namespace Riftweb\SuperSeeder\Traits;

trait Trackable
{
    public function run(): void
    {
        if (! $this->shouldRun()) {
            return;
        }

        $this->up();
        $this->markAsRun();
    }

    public function shouldRun(): bool
    {
        $executor = app('superseeder.executor');
        $service = app('superseeder.service');

        return config('superseeder.bypass') || $executor->isForced() || $service->seederDoesntExists(static::class);
    }

    abstract protected function up(): void;

    public function markAsRun(): void
    {
        $service = app('superseeder.service');
        $executor = app('superseeder.executor');

        if (! $service->store(static::class, $executor->currentBatch())) {
            $this->down();

            throw new \RuntimeException(sprintf('Unable to record execution for %s.', static::class));
        }
    }

    /**
     * Return the records created by this seeder when rollback dependency checks are required.
     *
     * @return array<string, array<string, list<int|string>>>
     */
    public function seededRecords(): array
    {
        return [];
    }

    abstract public function down(): void;
}
