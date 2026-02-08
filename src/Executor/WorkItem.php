<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Executor;

use TestFlowLabs\DocTest\CodeBlock\CodeBlock;

final readonly class WorkItem
{
    public function __construct(
        public int $index,
        public string $filePath,
        public CodeBlock $codeBlock,
    ) {}
}
