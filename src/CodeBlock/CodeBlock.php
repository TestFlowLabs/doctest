<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\CodeBlock;

use TestFlowLabs\DocTest\Assertion\Assertion;

final readonly class CodeBlock
{
    /**
     * @param  array<Assertion>  $assertions
     */
    public function __construct(
        public string $file,
        public int $startLine,
        public string $rawCode,
        public string $executableCode,
        public Attributes $attributes,
        public array $assertions,
    ) {}
}
