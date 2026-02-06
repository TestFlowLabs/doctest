<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Comparison\Normalizer;

beforeEach(function (): void {
    $this->normalizer = new Normalizer();
});
test('trim trailing disabled preserves trailing whitespace', function (): void {
    $normalizer = new Normalizer(normalizeWhitespace: true, trimTrailing: false);

    expect($normalizer->normalize("hello   \nworld\t\n"))->toBe("hello   \nworld\t\n");
});
test('normalize whitespace disabled preserves leading blank lines', function (): void {
    $normalizer = new Normalizer(normalizeWhitespace: false, trimTrailing: true);

    expect($normalizer->normalize("\n\ncontent\n"))->toBe("\n\ncontent\n");
});
test('normalize whitespace disabled preserves trailing blank lines', function (): void {
    $normalizer = new Normalizer(normalizeWhitespace: false, trimTrailing: true);

    expect($normalizer->normalize("content\n\n\n"))->toBe("content\n\n\n");
});
test('both disabled only converts crlf', function (): void {
    $normalizer = new Normalizer(normalizeWhitespace: false, trimTrailing: false);

    expect($normalizer->normalize("  hello   \r\n\r\n  world\t\r\n"))->toBe("  hello   \n\n  world\t\n");
});
test('both disabled returns empty for whitespace only', function (): void {
    $normalizer = new Normalizer(normalizeWhitespace: false, trimTrailing: false);

    expect($normalizer->normalize("   \n  \n  "))->toBe('');
});
test('converts crlf to lf', function (): void {
    expect($this->normalizer->normalize("line1\r\nline2\r\n"))->toBe("line1\nline2\n");
});
test('removes trailing whitespace from lines', function (): void {
    expect($this->normalizer->normalize("hello   \nworld\t\n"))->toBe("hello\nworld\n");
});
test('removes leading blank lines', function (): void {
    expect($this->normalizer->normalize("\n\n\ncontent\n"))->toBe("content\n");
});
test('removes trailing blank lines', function (): void {
    expect($this->normalizer->normalize("content\n\n\n"))->toBe("content\n");
});
test('normalizes to single trailing newline', function (): void {
    expect($this->normalizer->normalize('hello'))->toBe("hello\n");
    expect($this->normalizer->normalize("hello\n\n\n"))->toBe("hello\n");
});
test('handles empty string', function (): void {
    expect($this->normalizer->normalize(''))->toBe('');
});
test('preserves internal blank lines', function (): void {
    expect($this->normalizer->normalize("first\n\nsecond"))->toBe("first\n\nsecond\n");
});
test('preserves internal indentation', function (): void {
    expect($this->normalizer->normalize("  indented\n    more"))->toBe("  indented\n    more\n");
});
