<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Assertion;

final readonly class AssertionParserResult
{
    /**
     * @param array<ResultCommentAssertion> $resultComments
     */
    public function __construct(
        public string $executableCode,
        public array $resultComments = [],
    ) {}
}
