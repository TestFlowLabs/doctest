<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Executor;

use TestFlowLabs\DocTest\CodeBlock\CodeBlock;

final readonly class ParallelExecutor
{
    public function __construct(
        private CodeGenerator $codeGenerator,
        private WorkerPool $workerPool,
    ) {}

    /**
     * @param  array<CodeBlock>  $blocks
     *
     * @return array<int, ProcessResult> keyed by position in input array
     */
    public function execute(array $blocks, ?string $setup = null, ?string $teardown = null): array
    {
        if ($blocks === []) {
            return [];
        }

        $items = [];

        foreach ($blocks as $index => $block) {
            $filePath = $this->codeGenerator->generate($block, $setup, $teardown);
            $items[]  = new WorkItem($index, $filePath, $block);
        }

        $results = $this->workerPool->run($items);

        // Cleanup temp files
        foreach ($items as $item) {
            if (file_exists($item->filePath)) {
                @unlink($item->filePath);
            }
        }

        return $results;
    }
}
