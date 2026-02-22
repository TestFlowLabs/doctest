<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Assertion\ExpectAssertion;
use TestFlowLabs\DocTest\Assertion\OutputAssertion;
use TestFlowLabs\DocTest\Assertion\OutputJsonAssertion;
use TestFlowLabs\DocTest\Assertion\OutputMatchesAssertion;
use TestFlowLabs\DocTest\Assertion\OutputContainsAssertion;
use TestFlowLabs\DocTest\Assertion\HtmlCommentAssertionParser;

beforeEach(function (): void {
    $this->parser = new HtmlCommentAssertionParser();
});
test('parses output assertion', function (): void {
    $result = $this->parser->parse('<!-- doctest: Hello, World! -->');

    expect($result)->toHaveCount(1);
    expect($result[0])->toBeInstanceOf(OutputAssertion::class);
    expect($result[0]->expected)->toBe('Hello, World!');
});
test('parses contains assertion', function (): void {
    $result = $this->parser->parse('<!-- doctest-contains: World -->');

    expect($result)->toHaveCount(1);
    expect($result[0])->toBeInstanceOf(OutputContainsAssertion::class);
    expect($result[0]->expected)->toBe('World');
});
test('parses matches assertion', function (): void {
    $result = $this->parser->parse('<!-- doctest-matches: /\d+/ -->');

    expect($result)->toHaveCount(1);
    expect($result[0])->toBeInstanceOf(OutputMatchesAssertion::class);
    expect($result[0]->pattern)->toBe('/\d+/');
});
test('parses json assertion', function (): void {
    $result = $this->parser->parse('<!-- doctest-json: {"key":"value"} -->');

    expect($result)->toHaveCount(1);
    expect($result[0])->toBeInstanceOf(OutputJsonAssertion::class);
    expect($result[0]->expectedJson)->toBe('{"key":"value"}');
});
test('parses expect assertion', function (): void {
    $result = $this->parser->parse('<!-- doctest-expect: $result === 5 -->');

    expect($result)->toHaveCount(1);
    expect($result[0])->toBeInstanceOf(ExpectAssertion::class);
    expect($result[0]->expression)->toBe('$result === 5');
});
test('parses multi line output assertion', function (): void {
    $html = "<!-- doctest:\nHello\nWorld\n-->";

    $result = $this->parser->parse($html);

    expect($result)->toHaveCount(1);
    expect($result[0])->toBeInstanceOf(OutputAssertion::class);
    expect($result[0]->expected)->toBe("Hello\nWorld");
});
test('parses multi line json assertion', function (): void {
    $html = "<!-- doctest-json:\n{\n  \"key\": \"value\"\n}\n-->";

    $result = $this->parser->parse($html);

    expect($result)->toHaveCount(1);
    expect($result[0])->toBeInstanceOf(OutputJsonAssertion::class);
    expect($result[0]->expectedJson)->toBe("{\n  \"key\": \"value\"\n}");
});
test('parses multi line output with empty lines', function (): void {
    $html = "<!-- doctest:\nLine 1\n\nLine 3\n-->";

    $result = $this->parser->parse($html);

    expect($result)->toHaveCount(1);
    expect($result[0])->toBeInstanceOf(OutputAssertion::class);
    expect($result[0]->expected)->toBe("Line 1\n\nLine 3");
});
test('returns empty for non doctest comment', function (): void {
    $result = $this->parser->parse('<!-- just a regular comment -->');

    expect($result)->toHaveCount(0);
});
test('returns empty for non html input', function (): void {
    $result = $this->parser->parse('not an html comment');

    expect($result)->toHaveCount(0);
});
test('returns empty for empty string', function (): void {
    $result = $this->parser->parse('');

    expect($result)->toHaveCount(0);
});
test('trims leading whitespace before comment', function (): void {
    $result = $this->parser->parse('   <!-- doctest: Hello -->');

    expect($result)->toHaveCount(1);
    expect($result[0]->expected)->toBe('Hello');
});
test('trims whitespace around value', function (): void {
    $result = $this->parser->parse('<!-- doctest-contains:    World    -->');

    expect($result)->toHaveCount(1);
    expect($result[0]->expected)->toBe('World');
});
test('all assertion types use line zero by default', function (): void {
    $assertions = [
        $this->parser->parse('<!-- doctest: output -->'),
        $this->parser->parse('<!-- doctest-contains: value -->'),
        $this->parser->parse('<!-- doctest-matches: /pattern/ -->'),
        $this->parser->parse('<!-- doctest-json: {} -->'),
        $this->parser->parse('<!-- doctest-expect: true -->'),
    ];

    foreach ($assertions as $assertion) {
        expect($assertion)->toHaveCount(1);
        expect($assertion[0]->line())->toBe(0);
    }
});
test('passes markdown line to output assertion', function (): void {
    $result = $this->parser->parse('<!-- doctest: Hello -->', 15);

    expect($result)->toHaveCount(1);
    expect($result[0]->line())->toBe(15);
});
test('passes markdown line to json assertion', function (): void {
    $result = $this->parser->parse('<!-- doctest-json: {} -->', 20);

    expect($result)->toHaveCount(1);
    expect($result[0]->line())->toBe(20);
});
test('passes markdown line to contains assertion', function (): void {
    $result = $this->parser->parse('<!-- doctest-contains: value -->', 25);

    expect($result)->toHaveCount(1);
    expect($result[0]->line())->toBe(25);
});
test('passes markdown line to matches assertion', function (): void {
    $result = $this->parser->parse('<!-- doctest-matches: /pat/ -->', 30);

    expect($result)->toHaveCount(1);
    expect($result[0]->line())->toBe(30);
});
test('passes markdown line to expect assertion', function (): void {
    $result = $this->parser->parse('<!-- doctest-expect: true -->', 35);

    expect($result)->toHaveCount(1);
    expect($result[0]->line())->toBe(35);
});
test('returns empty for doctest comment without type', function (): void {
    $result = $this->parser->parse('<!-- doctest -->');

    expect($result)->toHaveCount(0);
});
test('parses multi line contains assertion', function (): void {
    $html = "<!-- doctest-contains:\nmultiline value\n-->";

    $result = $this->parser->parse($html);

    expect($result)->toHaveCount(1);
    expect($result[0])->toBeInstanceOf(OutputContainsAssertion::class);
    expect($result[0]->expected)->toBe('multiline value');
});
test('parses multi line expect assertion', function (): void {
    $html = "<!-- doctest-expect:\n\$x === 42\n-->";

    $result = $this->parser->parse($html);

    expect($result)->toHaveCount(1);
    expect($result[0])->toBeInstanceOf(ExpectAssertion::class);
    expect($result[0]->expression)->toBe('$x === 42');
});
test('returns empty for incomplete comment', function (): void {
    $result = $this->parser->parse('<!-- doctest: value');

    expect($result)->toHaveCount(0);
});
test('parses output assertion with special characters', function (): void {
    $result = $this->parser->parse('<!-- doctest: <div class="test">&amp; -->');

    expect($result)->toHaveCount(1);
    expect($result[0])->toBeInstanceOf(OutputAssertion::class);
    expect($result[0]->expected)->toBe('<div class="test">&amp;');
});
test('parses json with nested structure', function (): void {
    $result = $this->parser->parse('<!-- doctest-json: {"users":[{"name":"Alice"},{"name":"Bob"}]} -->');

    expect($result)->toHaveCount(1);
    expect($result[0])->toBeInstanceOf(OutputJsonAssertion::class);
    expect($result[0]->expectedJson)->toBe('{"users":[{"name":"Alice"},{"name":"Bob"}]}');
});
