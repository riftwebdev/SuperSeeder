<?php

namespace Riftweb\SuperSeeder\Services;

class SeederExecutorService
{
    protected static ?int $currentBatch = null;

    protected bool $force = false;

    /**
     * @var list<string>
     */
    protected array $tags = [];

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

    /**
     * @param  list<string>  $tags
     */
    public function setTags(array $tags): self
    {
        $this->tags = array_values(array_unique(array_filter($tags, fn (?string $tag): bool => filled($tag))));

        return $this;
    }

    /**
     * @return list<string>
     */
    public function requestedTags(): array
    {
        return $this->tags;
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
