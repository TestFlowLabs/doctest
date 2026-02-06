<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Config;

use SplFileInfo;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

final readonly class FileFinder
{
    /**
     * @param  array<string>  $paths
     * @param  array<string>  $exclude
     *
     * @return array<string>
     */
    public function find(array $paths, array $exclude): array
    {
        $files = [];

        foreach ($paths as $path) {
            if (is_file($path)) {
                $files[] = $path;
            } elseif (is_dir($path)) {
                $files = [...$files, ...$this->findInDirectory($path)];
            }
        }

        $files = array_unique($files);

        if ($exclude !== []) {
            $files = $this->applyExclusions($files, $exclude);
        }

        sort($files);

        return $files;
    }

    /**
     * @return list<string>
     */
    private function findInDirectory(string $directory): array
    {
        $files = [];

        try {
            /** @var RecursiveIteratorIterator<RecursiveDirectoryIterator> $iterator */
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory),
            );
        } catch (\UnexpectedValueException) {
            return [];
        }

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * @param  array<string>  $files
     * @param  array<string>  $exclude
     *
     * @return array<string>
     */
    private function applyExclusions(array $files, array $exclude): array
    {
        return array_filter($files, static function (string $file) use ($exclude): bool {
            foreach ($exclude as $pattern) {
                if (fnmatch($pattern, $file)) {
                    return false;
                }
                if (fnmatch($pattern, basename($file))) {
                    return false;
                }
            }

            return true;
        });
    }
}
