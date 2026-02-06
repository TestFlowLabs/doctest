<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Comparison\DiffGenerator;

beforeEach(function (): void {
    $this->diff = new DiffGenerator();
});
test('single line diff', function (): void {
    $result = $this->diff->generate("hello\n", "world\n");

    $this->assertStringContainsString('- hello', $result);
    $this->assertStringContainsString('+ world', $result);
});
test('multi line diff', function (): void {
    $result = $this->diff->generate("line1\nline2\n", "line1\nchanged\n");

    $this->assertStringContainsString('- line2', $result);
    $this->assertStringContainsString('+ changed', $result);
});
test('addition only', function (): void {
    $result = $this->diff->generate("line1\n", "line1\nline2\n");

    $this->assertStringContainsString('+ line2', $result);
});
test('removal only', function (): void {
    $result = $this->diff->generate("line1\nline2\n", "line1\n");

    $this->assertStringContainsString('- line2', $result);
});
test('identical strings produce empty diff', function (): void {
    $result = $this->diff->generate("same\n", "same\n");

    expect($result)->toBe('');
});
test('mixed changes', function (): void {
    $result = $this->diff->generate("a\nb\nc\n", "a\nB\nc\n");

    $this->assertStringContainsString('- b', $result);
    $this->assertStringContainsString('+ B', $result);
    $this->assertStringNotContainsString('- a', $result);
    $this->assertStringNotContainsString('- c', $result);
});
test('both empty strings produce empty diff', function (): void {
    $result = $this->diff->generate('', '');

    expect($result)->toBe('');
});
test('empty expected vs nonempty actual', function (): void {
    $result = $this->diff->generate('', "hello\n");

    $this->assertStringContainsString('+ hello', $result);
});
test('nonempty expected vs empty actual', function (): void {
    $result = $this->diff->generate("hello\n", '');

    $this->assertStringContainsString('- hello', $result);
});
test('strings without trailing newlines', function (): void {
    $result = $this->diff->generate('hello', 'world');

    $this->assertStringContainsString('- hello', $result);
    $this->assertStringContainsString('+ world', $result);
});
test('all lines different', function (): void {
    $result = $this->diff->generate("a\nb\nc\n", "x\ny\nz\n");

    $this->assertStringContainsString('- a', $result);
    $this->assertStringContainsString('- b', $result);
    $this->assertStringContainsString('- c', $result);
    $this->assertStringContainsString('+ x', $result);
    $this->assertStringContainsString('+ y', $result);
    $this->assertStringContainsString('+ z', $result);
});
