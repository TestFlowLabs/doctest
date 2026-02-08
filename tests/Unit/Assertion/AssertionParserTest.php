<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\Assertion\ResultCommentAssertion;

beforeEach(function (): void {
    $this->parser = new AssertionParser();
});
test('preserves non assertion comments', function (): void {
    $result = $this->parser->parse("// This is a regular comment\n\$x = 1;");

    $this->assertStringContainsString('// This is a regular comment', $result->executableCode);
});
test('handles block with no assertions', function (): void {
    $result = $this->parser->parse('$x = 42;');

    expect($result->resultComments)->toBeEmpty();
    expect($result->executableCode)->toBe('$x = 42;');
});
test('output comment is treated as regular comment', function (): void {
    $result = $this->parser->parse("echo \"Hello\";\n// Output: Hello");

    expect($result->resultComments)->toBeEmpty();
    $this->assertStringContainsString('// Output: Hello', $result->executableCode);
});
test('expect comment is treated as regular comment', function (): void {
    $result = $this->parser->parse("\$sum = 1 + 2;\n// Expect: \$sum === 3");

    expect($result->resultComments)->toBeEmpty();
    $this->assertStringContainsString('// Expect: $sum === 3', $result->executableCode);
});
test('output contains comment is treated as regular comment', function (): void {
    $result = $this->parser->parse("echo \"Hello World\";\n// OutputContains: World");

    expect($result->resultComments)->toBeEmpty();
    $this->assertStringContainsString('// OutputContains: World', $result->executableCode);
});
test('output matches comment is treated as regular comment', function (): void {
    $result = $this->parser->parse("echo \"Order #1234\";\n// OutputMatches: /Order #\\d{4}/");

    expect($result->resultComments)->toBeEmpty();
    $this->assertStringContainsString('// OutputMatches:', $result->executableCode);
});
test('output json comment is treated as regular comment', function (): void {
    $result = $this->parser->parse("echo json_encode(['key' => 'val']);\n// OutputJson: {\"key\": \"val\"}");

    expect($result->resultComments)->toBeEmpty();
    $this->assertStringContainsString('// OutputJson:', $result->executableCode);
});
test('finds simple result comment', function (): void {
    $result = $this->parser->parse('$x = 42; // => 42');

    expect($result->resultComments)->toHaveCount(1);
    expect($result->resultComments[0])->toBeInstanceOf(ResultCommentAssertion::class);
    expect($result->resultComments[0]->expression)->toBe('$x = 42');
    expect($result->resultComments[0]->expectedValue)->toBe('42');
});
test('finds boolean result comment', function (): void {
    $result = $this->parser->parse('$state->matches(\'green\'); // => true');

    expect($result->resultComments)->toHaveCount(1);
    expect($result->resultComments[0]->expression)->toBe('$state->matches(\'green\')');
    expect($result->resultComments[0]->expectedValue)->toBe('true');
});
test('finds string result comment', function (): void {
    $result = $this->parser->parse('$name = \'Alice\'; // => \'Alice\'');

    expect($result->resultComments)->toHaveCount(1);
    expect($result->resultComments[0]->expression)->toBe('$name = \'Alice\'');
    expect($result->resultComments[0]->expectedValue)->toBe('\'Alice\'');
});
test('finds null result comment', function (): void {
    $result = $this->parser->parse('$result = null; // => NULL');

    expect($result->resultComments)->toHaveCount(1);
    expect($result->resultComments[0]->expression)->toBe('$result = null');
    expect($result->resultComments[0]->expectedValue)->toBe('NULL');
});
test('strips semicolon from result comment expression', function (): void {
    $result = $this->parser->parse('$x = 42; // => 42');

    expect($result->resultComments[0]->expression)->toBe('$x = 42');
});
test('result comment preserves line number', function (): void {
    $result = $this->parser->parse("\$a = 1;\n\$b = 2; // => 2");

    expect($result->resultComments)->toHaveCount(1);
    expect($result->resultComments[0]->line())->toBe(2);
});
test('result comment keeps expression in executable code', function (): void {
    $result = $this->parser->parse('$x = 42; // => 42');

    $this->assertStringContainsString('$x = 42;', $result->executableCode);
    $this->assertStringNotContainsString('// =>', $result->executableCode);
});
test('handles multiple result comments', function (): void {
    $code   = "\$x = 1; // => 1\n\$y = 2; // => 2\n\$z = 3; // => 3";
    $result = $this->parser->parse($code);

    expect($result->resultComments)->toHaveCount(3);
    expect($result->resultComments[0]->expectedValue)->toBe('1');
    expect($result->resultComments[1]->expectedValue)->toBe('2');
    expect($result->resultComments[2]->expectedValue)->toBe('3');
});
test('result comment does not match regular comments', function (): void {
    $result = $this->parser->parse("// This is a regular comment\n\$x = 1;");

    expect($result->resultComments)->toBeEmpty();
});
test('result comment does not match arrow in array', function (): void {
    $result = $this->parser->parse("\$arr = ['key' => 'value'];");

    expect($result->resultComments)->toBeEmpty();
});
test('result comment handles spacing variations', function (): void {
    $result1 = $this->parser->parse('$x = 1; //=> 1');
    $result2 = $this->parser->parse('$x = 1; // =>1');
    $result3 = $this->parser->parse('$x = 1; //=>1');

    expect($result1->resultComments)->toHaveCount(1);
    expect($result2->resultComments)->toHaveCount(1);
    expect($result3->resultComments)->toHaveCount(1);
    expect($result1->resultComments[0]->expectedValue)->toBe('1');
    expect($result2->resultComments[0]->expectedValue)->toBe('1');
    expect($result3->resultComments[0]->expectedValue)->toBe('1');
});
test('result comment without semicolon', function (): void {
    $result = $this->parser->parse('is_string($x) // => false');

    expect($result->resultComments)->toHaveCount(1);
    expect($result->resultComments[0]->expression)->toBe('is_string($x)');
    expect($result->resultComments[0]->expectedValue)->toBe('false');
});
test('handles empty code', function (): void {
    $result = $this->parser->parse('');

    expect($result->resultComments)->toBeEmpty();
    expect($result->executableCode)->toBe('');
});
test('result comment adds semicolon to executable code', function (): void {
    $result = $this->parser->parse('is_string($x) // => false');

    $this->assertStringContainsString('is_string($x);', $result->executableCode);
});
test('url in code is not treated as result comment', function (): void {
    $result = $this->parser->parse('$url = "https://example.com";');

    expect($result->resultComments)->toBeEmpty();
    $this->assertStringContainsString('https://example.com', $result->executableCode);
});
test('result comment with array expected value', function (): void {
    $result = $this->parser->parse('$arr = [1, 2, 3]; // => array (0 => 1, 1 => 2, 2 => 3)');

    expect($result->resultComments)->toHaveCount(1);
    expect($result->resultComments[0]->expectedValue)->toBe('array (0 => 1, 1 => 2, 2 => 3)');
});
test('mixed result comments and regular lines', function (): void {
    $code   = "\$x = 1;\n\$y = \$x + 1; // => 2\n\$z = 3;";
    $result = $this->parser->parse($code);

    expect($result->resultComments)->toHaveCount(1);
    expect($result->resultComments[0]->expectedValue)->toBe('2');
    $this->assertStringContainsString('$x = 1;', $result->executableCode);
    $this->assertStringContainsString('$z = 3;', $result->executableCode);
});

test('dd() creates a debug marker instead of result comment', function (): void {
    $result = $this->parser->parse('$x = 42; // => dd()');

    expect($result->resultComments)->toBeEmpty();
    expect($result->debugMarkers)->toHaveCount(1);
    expect($result->debugMarkers[0]->expression)->toBe('$x = 42');
    expect($result->debugMarkers[0]->line())->toBe(1);
});

test('dd() strips comment from executable code', function (): void {
    $result = $this->parser->parse('$x = 42; // => dd()');

    $this->assertStringContainsString('$x = 42;', $result->executableCode);
    $this->assertStringNotContainsString('dd()', $result->executableCode);
});

test('multiple dd() markers on different lines', function (): void {
    $code   = "\$x = 42; // => dd()\n\$y = \$x * 2; // => dd()";
    $result = $this->parser->parse($code);

    expect($result->debugMarkers)->toHaveCount(2);
    expect($result->debugMarkers[0]->expression)->toBe('$x = 42');
    expect($result->debugMarkers[1]->expression)->toBe('$y = $x * 2');
});

test('dd() mixed with regular result comments', function (): void {
    $code   = "\$x = 42; // => dd()\n\$y = 10; // => 10";
    $result = $this->parser->parse($code);

    expect($result->debugMarkers)->toHaveCount(1);
    expect($result->resultComments)->toHaveCount(1);
    expect($result->debugMarkers[0]->expression)->toBe('$x = 42');
    expect($result->resultComments[0]->expectedValue)->toBe('10');
});

test('dd() without semicolon adds one to executable code', function (): void {
    $result = $this->parser->parse('strtoupper("hello") // => dd()');

    expect($result->debugMarkers)->toHaveCount(1);
    expect($result->debugMarkers[0]->expression)->toBe('strtoupper("hello")');
    $this->assertStringContainsString('strtoupper("hello");', $result->executableCode);
});
