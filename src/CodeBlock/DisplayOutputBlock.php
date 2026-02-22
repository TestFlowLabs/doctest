<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\CodeBlock;

final readonly class DisplayOutputBlock
{
    public function __construct(
        public int $contentStartLine,
        public int $contentEndLine,
        public ?int $lines = null,
        public ?int $tail = null,
    ) {}
}
