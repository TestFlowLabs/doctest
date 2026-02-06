<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Assertion\OutputAssertion;

test('construction with all properties', function (): void {
    $attributes = new Attributes();
    $assertions = [new OutputAssertion('Hello', 5)];

    $block = new CodeBlock(
        file: 'docs/README.md',
        startLine: 10,
        rawCode: 'echo "Hello";'."\n".'// Output: Hello',
        executableCode: 'echo "Hello";',
        attributes: $attributes,
        assertions: $assertions,
    );

    expect($block->file)->toBe('docs/README.md');
    expect($block->startLine)->toBe(10);
    expect($block->rawCode)->toBe('echo "Hello";'."\n".'// Output: Hello');
    expect($block->executableCode)->toBe('echo "Hello";');
    expect($block->attributes)->toBe($attributes);
    expect($block->assertions)->toBe($assertions);
});
test('block with no assertions', function (): void {
    $block = new CodeBlock(
        file: 'README.md',
        startLine: 1,
        rawCode: '$x = 42;',
        executableCode: '$x = 42;',
        attributes: new Attributes(),
        assertions: [],
    );

    expect($block->assertions)->toBeEmpty();
});
test('block with multiple assertions', function (): void {
    $assertions = [
        new OutputAssertion('first', 2),
        new OutputAssertion('second', 5),
    ];

    $block = new CodeBlock(
        file: 'test.md',
        startLine: 1,
        rawCode: 'code',
        executableCode: 'code',
        attributes: new Attributes(),
        assertions: $assertions,
    );

    expect($block->assertions)->toHaveCount(2);
});
