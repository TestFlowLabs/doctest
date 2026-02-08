<?php

declare(strict_types=1);

use TestFlowLabs\DocTest\Executor\WorkItem;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;

test('constructs with index, file path, and code block', function (): void {
    $block = new CodeBlock(
        file: 'test.md',
        startLine: 5,
        rawCode: 'echo 1;',
        executableCode: 'echo 1;',
        attributes: new Attributes(),
        assertions: [],
    );

    $item = new WorkItem(
        index: 0,
        filePath: '/tmp/doctest_abc.php',
        codeBlock: $block,
    );

    expect($item->index)->toBe(0);
    expect($item->filePath)->toBe('/tmp/doctest_abc.php');
    expect($item->codeBlock)->toBe($block);
});
