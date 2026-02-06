<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Assertion;

final readonly class OutputMatchesAssertion implements Assertion
{
    public function __construct(
        public string $pattern,
        private int $line,
    ) {}

    public function type(): string
    {
        return 'output_matches';
    }

    public function line(): int
    {
        return $this->line;
    }
}
