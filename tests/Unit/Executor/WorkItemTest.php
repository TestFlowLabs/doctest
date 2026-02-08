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

test('constructs group work item with multiple code blocks', function (): void {
    $block1 = new CodeBlock(
        file: 'test.md',
        startLine: 5,
        rawCode: 'echo 1;',
        executableCode: 'echo 1;',
        attributes: new Attributes(),
        assertions: [],
    );
    $block2 = new CodeBlock(
        file: 'test.md',
        startLine: 10,
        rawCode: 'echo 2;',
        executableCode: 'echo 2;',
        attributes: new Attributes(),
        assertions: [],
    );

    $item = new WorkItem(
        index: 3,
        filePath: '/tmp/doctest_group.php',
        codeBlock: $block1,
        groupBlocks: [$block1, $block2],
    );

    expect($item->isGroup())->toBeTrue();
    expect($item->groupBlocks)->toHaveCount(2);
});

test('non-group work item has empty group blocks', function (): void {
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

    expect($item->isGroup())->toBeFalse();
    expect($item->groupBlocks)->toBe([]);
});
