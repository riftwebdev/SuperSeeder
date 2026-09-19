<?php

namespace Riftweb\SuperSeeder\Services;

class SeederExecutorService
{
    protected static ?int $currentBatch = null;

    protected bool $force = false;

    public function __construct(
        protected SeederExecutionService $seederExecutionService
    ) {}

    public function setForce(bool $force): self
    {
        $this->force = $force;

        return $this;
    }

    public function isForced(): bool
    {
        return $this->force;
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
