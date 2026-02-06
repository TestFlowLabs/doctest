<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Comparison\OutputComparator;

beforeEach(function (): void {
    $this->comparator = new OutputComparator();
});
test('exact match passes', function (): void {
    $result = $this->comparator->compare("Hello\n", "Hello\n");

    expect($result->passed)->toBeTrue();
});
test('exact match fails', function (): void {
    $result = $this->comparator->compare("Hello\n", "World\n");

    expect($result->passed)->toBeFalse();
});
test('normalized match passes trailing whitespace', function (): void {
    $result = $this->comparator->compare("Hello   \n", "Hello\n");

    expect($result->passed)->toBeTrue();
});
test('normalized match passes crlf vs lf', function (): void {
    $result = $this->comparator->compare("Hello\r\n", "Hello\n");

    expect($result->passed)->toBeTrue();
});
test('empty expected vs empty actual passes', function (): void {
    $result = $this->comparator->compare('', '');

    expect($result->passed)->toBeTrue();
});
test('multi line comparison', function (): void {
    $result = $this->comparator->compare("line1\nline2\n", "line1\nline2\n");

    expect($result->passed)->toBeTrue();
});
test('failed result includes normalized values', function (): void {
    $result = $this->comparator->compare("expected\n", "actual\n");

    expect($result->passed)->toBeFalse();
    expect($result->normalizedExpected)->toBe("expected\n");
    expect($result->normalizedActual)->toBe("actual\n");
});
test('wildcard any matches', function (): void {
    $result = $this->comparator->compare('Hello from {{any}}', 'Hello from localhost');

    expect($result->passed)->toBeTrue();
});
test('wildcard int matches', function (): void {
    $result = $this->comparator->compare('Count: {{int}}', 'Count: 42');

    expect($result->passed)->toBeTrue();
});
test('wildcard float matches', function (): void {
    $result = $this->comparator->compare('Value: {{float}}', 'Value: 3.14');

    expect($result->passed)->toBeTrue();
});
test('wildcard date matches', function (): void {
    $result = $this->comparator->compare('Date: {{date}}', 'Date: 2026-02-06');

    expect($result->passed)->toBeTrue();
});
test('wildcard time matches', function (): void {
    $result = $this->comparator->compare('Time: {{time}}', 'Time: 14:30:00');

    expect($result->passed)->toBeTrue();
});
test('wildcard uuid matches', function (): void {
    $result = $this->comparator->compare('ID: {{uuid}}', 'ID: 550e8400-e29b-41d4-a716-446655440000');

    expect($result->passed)->toBeTrue();
});
test('wildcard multiline matches', function (): void {
    $result = $this->comparator->compare("Header\n{{...}}\nFooter", "Header\nsome dynamic\ncontent here\nFooter");

    expect($result->passed)->toBeTrue();
});
test('wildcard without match fails', function (): void {
    $result = $this->comparator->compare('Count: {{int}}', 'Count: abc');

    expect($result->passed)->toBeFalse();
});
test('compare json passes with same structure', function (): void {
    $result = $this->comparator->compareJson('{"a": 1, "b": 2}', '{"a":1,"b":2}');

    expect($result->passed)->toBeTrue();
});
test('compare json passes with different key order', function (): void {
    $result = $this->comparator->compareJson('{"a": 1, "b": 2}', '{"b":2,"a":1}');

    expect($result->passed)->toBeTrue();
});
test('compare json passes with nested different key order', function (): void {
    $result = $this->comparator->compareJson(
        '{"user": {"name": "Alice", "age": 30}, "roles": ["admin"]}',
        '{"roles":["admin"],"user":{"age":30,"name":"Alice"}}',
    );

    expect($result->passed)->toBeTrue();
});
test('compare json fails with different values', function (): void {
    $result = $this->comparator->compareJson('{"a": 1}', '{"a":2}');

    expect($result->passed)->toBeFalse();
});
test('compare json fails with different array order', function (): void {
    $result = $this->comparator->compareJson('["a", "b"]', '["b","a"]');

    expect($result->passed)->toBeFalse();
});
test('compare json fails with invalid expected', function (): void {
    $result = $this->comparator->compareJson('not json', '{"a":1}');

    expect($result->passed)->toBeFalse();
    $this->assertStringContainsString('Expected JSON is invalid', $result->normalizedExpected);
});
test('compare json fails with invalid actual', function (): void {
    $result = $this->comparator->compareJson('{"a": 1}', 'not json');

    expect($result->passed)->toBeFalse();
    $this->assertStringContainsString('Actual JSON output is invalid', $result->normalizedActual);
});
test('empty expected vs nonempty actual fails', function (): void {
    $result = $this->comparator->compare('', "Hello\n");

    expect($result->passed)->toBeFalse();
});
test('nonempty expected vs empty actual fails', function (): void {
    $result = $this->comparator->compare("Hello\n", '');

    expect($result->passed)->toBeFalse();
});
test('passed result includes normalized values', function (): void {
    $result = $this->comparator->compare("Hello  \r\n", "Hello\n");

    expect($result->passed)->toBeTrue();
    expect($result->normalizedExpected)->toBe("Hello\n");
    expect($result->normalizedActual)->toBe("Hello\n");
});
test('wildcard datetime matches', function (): void {
    $result = $this->comparator->compare('Created: {{datetime}}', 'Created: 2024-01-15T10:30:00+00:00');

    expect($result->passed)->toBeTrue();
});
test('multiple wildcards in same comparison', function (): void {
    $result = $this->comparator->compare(
        'User {{int}} scored {{float}} on {{date}}',
        'User 42 scored 9.5 on 2024-01-15',
    );

    expect($result->passed)->toBeTrue();
});
test('multiple wildcards fail when one mismatches', function (): void {
    $result = $this->comparator->compare(
        'User {{int}} scored {{float}}',
        'User abc scored 9.5',
    );

    expect($result->passed)->toBeFalse();
});
test('multiline wildcard matches single line between markers', function (): void {
    $result = $this->comparator->compare("Start\n{{...}}\nEnd", "Start\nmiddle\nEnd");

    expect($result->passed)->toBeTrue();
});
test('compare json both invalid', function (): void {
    $result = $this->comparator->compareJson('not json', 'also not json');

    expect($result->passed)->toBeFalse();
    $this->assertStringContainsString('Expected JSON is invalid', $result->normalizedExpected);
});
test('compare json empty objects match', function (): void {
    $result = $this->comparator->compareJson('{}', '{}');

    expect($result->passed)->toBeTrue();
});
test('compare json empty arrays match', function (): void {
    $result = $this->comparator->compareJson('[]', '[]');

    expect($result->passed)->toBeTrue();
});
test('compare json with null and boolean values', function (): void {
    $result = $this->comparator->compareJson(
        '{"active": true, "deleted": false, "note": null}',
        '{"note":null,"active":true,"deleted":false}',
    );

    expect($result->passed)->toBeTrue();
});
test('compare json deeply nested key order', function (): void {
    $expected = '{"a": {"b": {"c": 1, "d": 2}, "e": 3}, "f": 4}';
    $actual   = '{"f":4,"a":{"e":3,"b":{"d":2,"c":1}}}';

    $result = $this->comparator->compareJson($expected, $actual);

    expect($result->passed)->toBeTrue();
});
test('compare json different types fails', function (): void {
    $result = $this->comparator->compareJson('{"a": "1"}', '{"a": 1}');

    expect($result->passed)->toBeFalse();
});
