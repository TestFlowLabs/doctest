<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Assertion;

final readonly class AssertionResultDetail
{
    public function __construct(
        public string $type,
        public bool $passed,
        public string $expected,
        public string $actual,
        public int $line,
        public ?string $expression = null,
    ) {}
}
