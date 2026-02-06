<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Assertion\OutputJsonAssertion;

test('type returns output json', function (): void {
    $assertion = new OutputJsonAssertion('{"key": "value"}', 1);

    expect($assertion->type())->toBe('output_json');
});
test('has expected json', function (): void {
    $assertion = new OutputJsonAssertion('{"status": "ok"}', 5);

    expect($assertion->expectedJson)->toBe('{"status": "ok"}');
});
