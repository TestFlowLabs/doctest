<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Assertion\OutputMatchesAssertion;

test('type returns output matches', function (): void {
    $assertion = new OutputMatchesAssertion('/\d+/', 1);

    expect($assertion->type())->toBe('output_matches');
});
test('has pattern', function (): void {
    $assertion = new OutputMatchesAssertion('/Order #\d{4}/', 5);

    expect($assertion->pattern)->toBe('/Order #\d{4}/');
});
