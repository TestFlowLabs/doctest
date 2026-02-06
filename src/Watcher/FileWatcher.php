<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Watcher;

final class FileWatcher
{
    /** @var array<string, int> */
    private array $fileTimestamps = [];

    /**
     * @param array<string> $paths
     * @param array<string> $extensions
     */
    public function __construct(
        private readonly array $paths,
        private readonly array $extensions = ['md'],
    ) {}

    public function snapshot(): void
    {
        $this->fileTimestamps = $this->collectTimestamps();
    }

    /**
     * @return array<string>
     */
    public function getChangedFiles(): array
    {
        $current = $this->collectTimestamps();
        $changed = [];

        foreach ($current as $file => $mtime) {
            if (! isset($this->fileTimestamps[$file]) || $this->fileTimestamps[$file] < $mtime) {
                $changed[] = $file;
            }
        }

        return $changed;
    }

    /**
     * @return array<string, int>
     */
    private function collectTimestamps(): array
    {
        $timestamps = [];

        foreach ($this->paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $this->scanDirectory($path, $timestamps);
        }

        return $timestamps;
    }

    /**
     * @param array<string, int> $timestamps
     */
    private function scanDirectory(string $dir, array &$timestamps): void
    {
        $entries = scandir($dir);

        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $dir . '/' . $entry;

            if (is_dir($fullPath)) {
                $this->scanDirectory($fullPath, $timestamps);

                continue;
            }

            $extension = pathinfo($fullPath, PATHINFO_EXTENSION);

            if (in_array($extension, $this->extensions, true)) {
                $timestamps[$fullPath] = filemtime($fullPath) ?: 0;
            }
        }
    }
}
