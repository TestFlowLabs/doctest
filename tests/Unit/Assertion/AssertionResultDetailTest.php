<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Assertion\AssertionResultDetail;

test('stores all properties', function (): void {
    $detail = new AssertionResultDetail(
        type: 'output',
        passed: true,
        expected: 'Hello',
        actual: 'Hello',
        line: 5,
    );

    expect($detail->type)->toBe('output');
    expect($detail->passed)->toBeTrue();
    expect($detail->expected)->toBe('Hello');
    expect($detail->actual)->toBe('Hello');
    expect($detail->line)->toBe(5);
});
test('stores failed assertion', function (): void {
    $detail = new AssertionResultDetail(
        type: 'result_comment',
        passed: false,
        expected: '99',
        actual: '42',
        line: 10,
    );

    expect($detail->passed)->toBeFalse();
    expect($detail->expected)->toBe('99');
    expect($detail->actual)->toBe('42');
});
test('stores expect assertion with expression', function (): void {
    $detail = new AssertionResultDetail(
        type: 'expect',
        passed: true,
        expected: '$x === 42',
        actual: 'true',
        line: 3,
    );

    expect($detail->type)->toBe('expect');
    expect($detail->expected)->toBe('$x === 42');
});
test('has nullable expression', function (): void {
    $detail = new AssertionResultDetail(
        type: 'result_comment',
        passed: true,
        expected: '42',
        actual: '42',
        line: 1,
        expression: '$x = 42',
    );

    expect($detail->expression)->toBe('$x = 42');
});
test('expression defaults to null', function (): void {
    $detail = new AssertionResultDetail(
        type: 'output',
        passed: true,
        expected: 'test',
        actual: 'test',
        line: 1,
    );

    expect($detail->expression)->toBeNull();
});
