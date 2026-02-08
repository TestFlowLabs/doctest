<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Assertion;

final readonly class DebugMarker
{
    public function __construct(
        public string $expression,
        private int $line,
    ) {}

    public function line(): int
    {
        return $this->line;
    }
}
