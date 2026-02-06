<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Assertion;

final readonly class ExpectAssertion implements Assertion
{
    public function __construct(
        public string $expression,
        private int $line,
    ) {}

    public function type(): string
    {
        return 'expect';
    }

    public function line(): int
    {
        return $this->line;
    }
}
