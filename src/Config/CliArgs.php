<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Config;

final readonly class CliArgs
{
    /**
     * @param array<string> $files
     */
    public function __construct(
        public array $files,
        public ?string $filter,
        public ?string $exclude,
        public bool $dryRun,
        public bool $stopOnFailure,
        public int $verbosity,
        public ?string $configPath,
    ) {}

    /**
     * @param array<string> $argv
     */
    public static function parse(array $argv): self
    {
        // Skip the script name (first element)
        $args = array_slice($argv, 1);

        $files = [];
        $filter = null;
        $exclude = null;
        $dryRun = false;
        $stopOnFailure = false;
        $verbosity = 0;
        $configPath = null;

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--filter=')) {
                $filter = substr($arg, 9);
            } elseif (str_starts_with($arg, '--exclude=')) {
                $exclude = substr($arg, 10);
            } elseif ($arg === '--dry-run') {
                $dryRun = true;
            } elseif ($arg === '--stop-on-failure') {
                $stopOnFailure = true;
            } elseif ($arg === '-v') {
                $verbosity = 1;
            } elseif ($arg === '-vv') {
                $verbosity = 2;
            } elseif ($arg === '-vvv') {
                $verbosity = 3;
            } elseif (str_starts_with($arg, '--config=')) {
                $configPath = substr($arg, 9);
            } elseif (! str_starts_with($arg, '-')) {
                $files[] = $arg;
            }
        }

        return new self(
            files: $files,
            filter: $filter,
            exclude: $exclude,
            dryRun: $dryRun,
            stopOnFailure: $stopOnFailure,
            verbosity: $verbosity,
            configPath: $configPath,
        );
    }
}
