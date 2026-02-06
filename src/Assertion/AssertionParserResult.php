<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Assertion;

final readonly class AssertionParserResult
{
    /**
     * @param array<Assertion> $assertions
     * @param array<CodeSegment> $segments
     * @param array<ExpectAssertion> $expects
     * @param array<ResultCommentAssertion> $resultComments
     */
    public function __construct(
        public array $assertions,
        public string $executableCode,
        public array $segments,
        public array $expects,
        public array $resultComments = [],
    ) {}
}
