<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Assertion;

final readonly class OutputContainsAssertion implements Assertion
{
    public function __construct(
        public string $expected,
        private int $line,
    ) {}

    public function type(): string
    {
        return 'output_contains';
    }

    public function line(): int
    {
        return $this->line;
    }
}
