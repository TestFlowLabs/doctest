<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Assertion;

final readonly class CodeSegment
{
    public function __construct(
        public string $code,
        public ?Assertion $outputAssertion = null,
    ) {}
}
