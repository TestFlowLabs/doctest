<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Config;

final readonly class DocTestConfig
{
    /**
     * @param  array<string>  $paths
     * @param  array<string>  $exclude
     */
    public function __construct(
        public array $paths = ['docs', 'README.md'],
        public array $exclude = [],
        public int $timeout = 30,
        public string $memoryLimit = '256M',
        public bool $stopOnFailure = false,
        public bool $dryRun = false,
        public ?string $filter = null,
        public int $verbosity = 0,
        public ?string $bootstrap = null,
        public bool $normalizeWhitespace = true,
        public bool $trimTrailing = true,
        public bool $reporterConsole = true,
        public ?string $reporterJunit = null,
        public ?string $reporterJson = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array<string, mixed> $execution */
        $execution = is_array($data['execution'] ?? null) ? $data['execution'] : [];
        /** @var array<string, mixed> $output */
        $output = is_array($data['output'] ?? null) ? $data['output'] : [];
        /** @var array<string, mixed> $reporters */
        $reporters = is_array($data['reporters'] ?? null) ? $data['reporters'] : [];

        /** @var array<string> $paths */
        $paths = is_array($data['paths'] ?? null) ? $data['paths'] : ['docs', 'README.md'];
        /** @var array<string> $exclude */
        $exclude = is_array($data['exclude'] ?? null) ? $data['exclude'] : [];

        return new self(
            paths: $paths,
            exclude: $exclude,
            timeout: is_int($execution['timeout'] ?? null) ? $execution['timeout'] : 30,
            memoryLimit: is_string($execution['memory_limit'] ?? null) ? $execution['memory_limit'] : '256M',
            stopOnFailure: (bool) ($data['stop_on_failure'] ?? $execution['stop_on_failure'] ?? false),
            dryRun: (bool) ($data['dry_run'] ?? false),
            filter: is_string($data['filter'] ?? null) ? $data['filter'] : null,
            verbosity: is_int($data['verbosity'] ?? null) ? $data['verbosity'] : 0,
            bootstrap: is_string($data['bootstrap'] ?? null) ? $data['bootstrap'] : null,
            normalizeWhitespace: (bool) ($output['normalize_whitespace'] ?? true),
            trimTrailing: (bool) ($output['trim_trailing'] ?? true),
            reporterConsole: (bool) ($reporters['console'] ?? true),
            reporterJunit: is_string($reporters['junit'] ?? null) ? $reporters['junit'] : null,
            reporterJson: is_string($reporters['json'] ?? null) ? $reporters['json'] : null,
        );
    }

    public static function load(?string $configPath = null): self
    {
        $path = $configPath ?? getcwd().'/doctest.php';

        if (!file_exists($path)) {
            return new self();
        }

        $data = require $path;

        if (!is_array($data)) {
            return new self();
        }

        return self::fromArray($data); // @phpstan-ignore argument.type (require returns mixed, is_array narrows to array<mixed,mixed> not array<string,mixed>)
    }
}
