<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Comparison\WildcardMatcher;

beforeEach(function (): void {
    $this->matcher = new WildcardMatcher();
});
test('any matches non empty string', function (): void {
    expect($this->matcher->matches('Hello World', '{{any}}'))->toBeTrue();
    expect($this->matcher->matches('', '{{any}}'))->toBeFalse();
});
test('int matches integers', function (): void {
    expect($this->matcher->matches('42', '{{int}}'))->toBeTrue();
    expect($this->matcher->matches('-7', '{{int}}'))->toBeTrue();
    expect($this->matcher->matches('3.14', '{{int}}'))->toBeFalse();
});
test('float matches floats', function (): void {
    expect($this->matcher->matches('3.14', '{{float}}'))->toBeTrue();
    expect($this->matcher->matches('-0.5', '{{float}}'))->toBeTrue();
    expect($this->matcher->matches('42', '{{float}}'))->toBeTrue();
});
test('uuid matches uuid v4', function (): void {
    expect($this->matcher->matches('550e8400-e29b-41d4-a716-446655440000', '{{uuid}}'))->toBeTrue();
    expect($this->matcher->matches('not-a-uuid', '{{uuid}}'))->toBeFalse();
});
test('datetime matches iso 8601', function (): void {
    expect($this->matcher->matches('2024-01-15T10:30:00', '{{datetime}}'))->toBeTrue();
    expect($this->matcher->matches('2024-01-15T10:30:00+00:00', '{{datetime}}'))->toBeTrue();
});
test('date matches ymd', function (): void {
    expect($this->matcher->matches('2024-01-15', '{{date}}'))->toBeTrue();
    expect($this->matcher->matches('01-15-2024', '{{date}}'))->toBeFalse();
});
test('time matches his', function (): void {
    expect($this->matcher->matches('10:30:00', '{{time}}'))->toBeTrue();
    expect($this->matcher->matches('10:30', '{{time}}'))->toBeFalse();
});
test('ellipsis matches any text including newlines', function (): void {
    expect($this->matcher->matches("line1\nline2\nline3", '{{...}}'))->toBeTrue();
    expect($this->matcher->matches('', '{{...}}'))->toBeTrue();
});
test('mixed wildcards and literal', function (): void {
    expect($this->matcher->matches('ID: 42, Name: John', 'ID: {{int}}, Name: {{any}}'))->toBeTrue();
    expect($this->matcher->matches('ID: abc, Name: John', 'ID: {{int}}, Name: {{any}}'))->toBeFalse();
});
test('no wildcards means exact match', function (): void {
    expect($this->matcher->matches('hello', 'hello'))->toBeTrue();
    expect($this->matcher->matches('hello', 'world'))->toBeFalse();
});
test('unknown placeholder treated as literal', function (): void {
    expect($this->matcher->matches('{{unknown}}', '{{unknown}}'))->toBeTrue();
    expect($this->matcher->matches('hello', '{{unknown}}'))->toBeFalse();
});
test('has wildcards returns true for known placeholders', function (): void {
    expect($this->matcher->hasWildcards('Hello {{any}}'))->toBeTrue();
    expect($this->matcher->hasWildcards('Count: {{int}}'))->toBeTrue();
    expect($this->matcher->hasWildcards('{{...}}'))->toBeTrue();
});
test('has wildcards returns false for no placeholders', function (): void {
    expect($this->matcher->hasWildcards('no wildcards here'))->toBeFalse();
    expect($this->matcher->hasWildcards(''))->toBeFalse();
});
test('has wildcards returns false for unknown placeholders', function (): void {
    expect($this->matcher->hasWildcards('{{unknown}}'))->toBeFalse();
});
test('pattern with special regex characters', function (): void {
    expect($this->matcher->matches('price: $42.00 (USD)', 'price: ${{float}} (USD)'))->toBeTrue();
});
test('float rejects non numeric', function (): void {
    expect($this->matcher->matches('abc', '{{float}}'))->toBeFalse();
});
test('datetime rejects non date', function (): void {
    expect($this->matcher->matches('not-a-date', '{{datetime}}'))->toBeFalse();
});
test('multiple same wildcards in pattern', function (): void {
    expect($this->matcher->matches('1 + 2 = 3', '{{int}} + {{int}} = {{int}}'))->toBeTrue();
});
