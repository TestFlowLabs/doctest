<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\CodeBlock\DisplayOutputBlock;

test('constructs with required properties', function (): void {
    $block = new DisplayOutputBlock(
        contentStartLine: 12,
        contentEndLine: 14,
    );

    expect($block->contentStartLine)->toBe(12);
    expect($block->contentEndLine)->toBe(14);
    expect($block->lines)->toBeNull();
    expect($block->tail)->toBeNull();
});
test('constructs with lines option', function (): void {
    $block = new DisplayOutputBlock(
        contentStartLine: 12,
        contentEndLine: 14,
        lines: 5,
    );

    expect($block->lines)->toBe(5);
});
test('constructs with tail option', function (): void {
    $block = new DisplayOutputBlock(
        contentStartLine: 12,
        contentEndLine: 14,
        tail: 3,
    );

    expect($block->tail)->toBe(3);
});
