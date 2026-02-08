<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Executor;

use TestFlowLabs\DocTest\CodeBlock\CodeBlock;

final readonly class WorkItem
{
    /**
     * @param  array<CodeBlock>  $groupBlocks
     */
    public function __construct(
        public int $index,
        public string $filePath,
        public CodeBlock $codeBlock,
        public array $groupBlocks = [],
    ) {}

    public function isGroup(): bool
    {
        return $this->groupBlocks !== [];
    }
}
