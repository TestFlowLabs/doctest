<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Assertion;

final readonly class OutputAssertion implements Assertion
{
    public function __construct(
        public string $expected,
        private int $line,
    ) {}

    public function type(): string
    {
        return 'output';
    }

    public function line(): int
    {
        return $this->line;
    }
}
