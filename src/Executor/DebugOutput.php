<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Executor;

final readonly class DebugOutput
{
    public function __construct(
        public string $expression,
        public string $value,
        public int $line,
    ) {}
}
