<?php

namespace Riftweb\SuperSeeder\Services;

class SeederStatusService
{
    public function __construct(
        protected SeederDiscoveryService $seederDiscoveryService,
        protected SeederExecutionService $seederExecutionService,
    ) {}

    /**
     * @return array<int, array{seeder: string, status: string, batch: string|int, time: string, executed_at: string}>
     */
    public function rows(?string $class = null): array
    {
        $seeders = collect($this->seederDiscoveryService->discover($class))
            ->merge($class
                ? $this->seederExecutionService->getTrackedSeederClasses()->filter(fn (string $trackedClass): bool => $trackedClass === $class)->all()
                : $this->seederExecutionService->getTrackedSeederClasses()->all())
            ->unique()
            ->sort()
            ->values();

        return $seeders->map(function (string $seeder): array {
            $execution = $this->seederExecutionService->getLatestExecutionForSeeder($seeder);

            return [
                'seeder' => class_basename($seeder),
                'status' => $execution ? 'Ran' : 'Pending',
                'batch' => $execution?->batch ?? '—',
                'time' => $execution?->execution_time_ms !== null ? sprintf('%d ms', $execution->execution_time_ms) : '—',
                'executed_at' => $execution?->created_at?->toDateTimeString() ?? '—',
            ];
        })->all();
    }
}
