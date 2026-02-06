<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Comparison;

final readonly class ComparisonResult
{
    public function __construct(
        public bool $passed,
        public string $normalizedExpected,
        public string $normalizedActual,
    ) {}
}
