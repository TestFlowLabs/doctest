<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Executor;

final readonly class ProcessResult
{
    public function __construct(
        public string $stdout,
        public string $stderr,
        public int $exitCode,
        public float $duration,
    ) {}
}
