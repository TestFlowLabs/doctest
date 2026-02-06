<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Assertion\Assertion;
use TestFlowLabs\DocTest\Assertion\ResultCommentAssertion;

test('implements assertion interface', function (): void {
    $assertion = new ResultCommentAssertion('$x', 'true', 1);

    expect($assertion)->toBeInstanceOf(Assertion::class);
});
test('returns result comment type', function (): void {
    $assertion = new ResultCommentAssertion('$x', 'true', 1);

    expect($assertion->type())->toBe('result_comment');
});
test('stores expression', function (): void {
    $assertion = new ResultCommentAssertion('$state->matches(\'green\')', 'true', 5);

    expect($assertion->expression)->toBe('$state->matches(\'green\')');
});
test('stores expected value', function (): void {
    $assertion = new ResultCommentAssertion('$x', '42', 3);

    expect($assertion->expectedValue)->toBe('42');
});
test('stores line number', function (): void {
    $assertion = new ResultCommentAssertion('$x', 'true', 10);

    expect($assertion->line())->toBe(10);
});
