<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Assertion\OutputContainsAssertion;

test('type returns output contains', function (): void {
    $assertion = new OutputContainsAssertion('hello', 1);

    expect($assertion->type())->toBe('output_contains');
});
test('has expected substring', function (): void {
    $assertion = new OutputContainsAssertion('world', 5);

    expect($assertion->expected)->toBe('world');
});
test('line returns correct line', function (): void {
    $assertion = new OutputContainsAssertion('test', 42);

    expect($assertion->line())->toBe(42);
});
