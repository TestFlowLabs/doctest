<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Assertion\Assertion;
use TestFlowLabs\DocTest\Assertion\OutputAssertion;

test('implements assertion interface', function (): void {
    $assertion = new OutputAssertion('Hello', 5);

    expect($assertion)->toBeInstanceOf(Assertion::class);
});
test('type returns output', function (): void {
    $assertion = new OutputAssertion('Hello', 5);

    expect($assertion->type())->toBe('output');
});
test('line returns correct value', function (): void {
    $assertion = new OutputAssertion('Hello', 42);

    expect($assertion->line())->toBe(42);
});
test('expected property is accessible', function (): void {
    $assertion = new OutputAssertion('Hello, World!', 10);

    expect($assertion->expected)->toBe('Hello, World!');
});
test('handles multiline expected', function (): void {
    $expected  = "Line 1\nLine 2\nLine 3";
    $assertion = new OutputAssertion($expected, 15);

    expect($assertion->expected)->toBe($expected);
});
