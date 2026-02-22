<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Assertion\DisplayOutputDirective;

test('constructs with defaults', function (): void {
    $directive = new DisplayOutputDirective(markdownLine: 10);

    expect($directive->markdownLine)->toBe(10);
    expect($directive->lines)->toBeNull();
    expect($directive->tail)->toBeNull();
});
test('constructs with lines option', function (): void {
    $directive = new DisplayOutputDirective(markdownLine: 10, lines: 5);

    expect($directive->lines)->toBe(5);
});
test('constructs with tail option', function (): void {
    $directive = new DisplayOutputDirective(markdownLine: 10, tail: 3);

    expect($directive->tail)->toBe(3);
});
