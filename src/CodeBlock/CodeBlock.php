<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\CodeBlock;

use TestFlowLabs\DocTest\Assertion\Assertion;
use TestFlowLabs\DocTest\Assertion\DebugMarker;
use TestFlowLabs\DocTest\Assertion\ResultCommentAssertion;

final readonly class CodeBlock
{
    /**
     * @param  array<Assertion>  $assertions
     * @param  array<ResultCommentAssertion>  $resultComments
     * @param  array<DebugMarker>  $debugMarkers
     */
    public function __construct(
        public string $file,
        public int $startLine,
        public string $rawCode,
        public string $executableCode,
        public Attributes $attributes,
        public array $assertions,
        public ?DisplayOutputBlock $displayOutput = null,
        public array $resultComments = [],
        public array $debugMarkers = [],
    ) {}
}
