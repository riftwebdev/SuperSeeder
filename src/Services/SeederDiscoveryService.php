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
            return [$class];
        }

        $seeders = [];
        $seedersPath = $this->seedersPath();

        if ($seedersPath && is_dir($seedersPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($seedersPath));

            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $seederClass = $this->classFromPath($file->getPathname(), $seedersPath);

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

    protected function seedersPath(): ?string
    {
        if (function_exists('database_path')) {
            return database_path('seeders');
        }

        return app()->basePath('database/seeders');
    }

    protected function classFromPath(string $path, string $seedersPath): ?string
    {
        $relativePath = ltrim(str_replace($seedersPath, '', $path), DIRECTORY_SEPARATOR);
        $classSuffix = str_replace(
            [DIRECTORY_SEPARATOR, '.php'],
            ['\\', ''],
            $relativePath,
        );

        $namespace = app()->getNamespace().'Database\\Seeders\\';

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
