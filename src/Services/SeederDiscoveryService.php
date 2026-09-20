<?php

namespace Riftweb\SuperSeeder\Services;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Riftweb\SuperSeeder\Traits\Trackable;

class SeederDiscoveryService
{
    /**
     * @return list<class-string>
     */
    public function discover(?string $class = null): array
    {
        if ($class) {
            return class_exists($class) && $this->usesTrackable($class) ? [$class] : [];
        }

        $seeders = [];

        foreach ($this->sources() as $source) {
            if (! is_dir($source['path'])) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source['path']));

            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $seederClass = $this->classFromPath($file->getPathname(), $source['path'], $source['namespace']);

                if (! $seederClass) {
                    continue;
                }

                if (class_exists($seederClass) && $this->usesTrackable($seederClass)) {
                    $seeders[] = $seederClass;
                }
            }
        }

        return array_values(array_unique($seeders));
    }

    /**
     * @return list<array{path: string, namespace: string}>
     */
    protected function sources(): array
    {
        $configuredSources = config('superseeder.seeder_sources');

        if (is_array($configuredSources) && $configuredSources !== []) {
            return array_values(array_filter($configuredSources, function (mixed $source): bool {
                return is_array($source)
                    && is_string($source['path'] ?? null)
                    && is_string($source['namespace'] ?? null);
            }));
        }

        return [[
            'path' => function_exists('database_path')
                ? database_path('seeders')
                : app()->basePath('database/seeders'),
            'namespace' => config('superseeder.seeders_namespace')
                ?: app()->getNamespace().'Database\\Seeders\\',
        ]];
    }

    protected function classFromPath(string $path, string $seedersPath, string $namespace): ?string
    {
        $relativePath = ltrim(str_replace($seedersPath, '', $path), DIRECTORY_SEPARATOR);
        $classSuffix = str_replace(
            [DIRECTORY_SEPARATOR, '.php'],
            ['\\', ''],
            $relativePath,
        );

        return $classSuffix === '' ? null : $namespace.$classSuffix;
    }

    /**
     * @param  class-string  $class
     */
    protected function usesTrackable(string $class): bool
    {
        return in_array(Trackable::class, class_uses_recursive($class), true);
    }
}
