<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Assertion\Assertion;
use TestFlowLabs\DocTest\Assertion\ExpectAssertion;

test('implements assertion interface', function (): void {
    $assertion = new ExpectAssertion('$x === 5', 10);

    expect($assertion)->toBeInstanceOf(Assertion::class);
});
test('type returns expect', function (): void {
    $assertion = new ExpectAssertion('$x === 5', 10);

    expect($assertion->type())->toBe('expect');
});
test('line returns correct value', function (): void {
    $assertion = new ExpectAssertion('$x === 5', 42);

    expect($assertion->line())->toBe(42);
});
test('expression property is accessible', function (): void {
    $assertion = new ExpectAssertion('count($items) > 0', 7);

    expect($assertion->expression)->toBe('count($items) > 0');
});
