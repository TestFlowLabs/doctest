<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Assertion;

final readonly class OutputJsonAssertion implements Assertion
{
    public function __construct(
        public string $expectedJson,
        private int $line,
    ) {}

    public function type(): string
    {
        return 'output_json';
    }

    public function line(): int
    {
        return $this->line;
    }
}
