<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Executor;

use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\Assertion\AssertionResultDetail;

final readonly class ExecutionResult
{
    /**
     * @param  array<AssertionResultDetail>  $assertionDetails
     */
    public function __construct(
        public bool $passed,
        public CodeBlock $codeBlock,
        public ?string $actualOutput = null,
        public ?string $expectedOutput = null,
        public ?string $diff = null,
        public ?string $error = null,
        public float $duration = 0.0,
        public bool $skipped = false,
        public array $assertionDetails = [],
    ) {}
}
