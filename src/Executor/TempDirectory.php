<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Executor;

final readonly class TempDirectory
{
    private function __construct(
        public string $path,
    ) {}

    public static function create(): self
    {
        $path = sys_get_temp_dir().'/doctest_run_'.bin2hex(random_bytes(8));

        if (!mkdir($path, 0700, true) && !is_dir($path)) {
            throw new \RuntimeException("Failed to create temp directory: {$path}");
        }

        return new self($path);
    }

    public function filePath(string $prefix): string
    {
        return $this->path.'/'.$prefix.'_'.bin2hex(random_bytes(8)).'.php';
    }

    public function cleanup(): void
    {
        if (!is_dir($this->path)) {
            return;
        }

        $files = glob($this->path.'/*');

        if ($files !== false) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }

        @rmdir($this->path);
    }
}
