<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Assertion;

final readonly class ResultCommentAssertion implements Assertion
{
    public function __construct(
        public string $expression,
        public string $expectedValue,
        private int $line,
    ) {}

    public function type(): string
    {
        return 'result_comment';
    }

    public function line(): int
    {
        return $this->line;
    }
}
