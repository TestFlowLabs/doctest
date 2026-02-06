<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\CodeBlock\Attribute;
use TestFlowLabs\DocTest\Parser\AttributeParser;

beforeEach(function (): void {
    $this->parser = new AttributeParser();
});
test('parses empty info string', function (): void {
    $attributes = $this->parser->parse('php');

    expect($attributes->attribute)->toBeNull();
    expect($attributes->group)->toBeNull();
});
test('parses ignore', function (): void {
    $attributes = $this->parser->parse('php ignore');

    expect($attributes->attribute)->toBe(Attribute::Ignore);
});
test('parses no run', function (): void {
    $attributes = $this->parser->parse('php no_run');

    expect($attributes->attribute)->toBe(Attribute::NoRun);
});
test('parses bare throws', function (): void {
    $attributes = $this->parser->parse('php throws');

    expect($attributes->attribute)->toBe(Attribute::Throws);
    expect($attributes->throwsClass)->toBeNull();
    expect($attributes->throwsMessage)->toBeNull();
});
test('parses throws with class', function (): void {
    $attributes = $this->parser->parse('php throws(InvalidArgumentException)');

    expect($attributes->attribute)->toBe(Attribute::Throws);
    expect($attributes->throwsClass)->toBe('InvalidArgumentException');
    expect($attributes->throwsMessage)->toBeNull();
});
test('parses throws with class and message', function (): void {
    $attributes = $this->parser->parse('php throws(InvalidArgumentException, "Bad input")');

    expect($attributes->attribute)->toBe(Attribute::Throws);
    expect($attributes->throwsClass)->toBe('InvalidArgumentException');
    expect($attributes->throwsMessage)->toBe('Bad input');
});
test('parses parse error', function (): void {
    $attributes = $this->parser->parse('php parse_error');

    expect($attributes->attribute)->toBe(Attribute::ParseError);
});
test('parses setup', function (): void {
    $attributes = $this->parser->parse('php setup');

    expect($attributes->attribute)->toBe(Attribute::Setup);
});
test('parses teardown', function (): void {
    $attributes = $this->parser->parse('php teardown');

    expect($attributes->attribute)->toBe(Attribute::Teardown);
});
test('parses group', function (): void {
    $attributes = $this->parser->parse('php group="order-flow"');

    expect($attributes->group)->toBe('order-flow');
});
test('ignores unknown attributes', function (): void {
    $attributes = $this->parser->parse('php unknown_thing');

    expect($attributes->attribute)->toBeNull();
});
test('group combined with attribute', function (): void {
    $attributes = $this->parser->parse('php group="test" throws');

    expect($attributes->attribute)->toBe(Attribute::Throws);
    expect($attributes->group)->toBe('test');
});
test('parses throws with namespaced class', function (): void {
    $attributes = $this->parser->parse('php throws(App\\Exceptions\\CustomException)');

    expect($attributes->attribute)->toBe(Attribute::Throws);
    expect($attributes->throwsClass)->toBe('App\\Exceptions\\CustomException');
});
test('group combined with throws class and message', function (): void {
    $attributes = $this->parser->parse('php group="grp" throws(RuntimeException, "msg")');

    expect($attributes->attribute)->toBe(Attribute::Throws);
    expect($attributes->throwsClass)->toBe('RuntimeException');
    expect($attributes->throwsMessage)->toBe('msg');
    expect($attributes->group)->toBe('grp');
});
test('first attribute wins when multiple present', function (): void {
    $attributes = $this->parser->parse('php ignore no_run');

    expect($attributes->attribute)->toBe(Attribute::Ignore);
});
test('strips shiki highlight before parsing attributes', function (): void {
    $attributes = $this->parser->parse('php{1,4-6} ignore');

    expect($attributes->attribute)->toBe(Attribute::Ignore);
});
test('empty string returns no attributes', function (): void {
    $attributes = $this->parser->parse('');

    expect($attributes->attribute)->toBeNull();
    expect($attributes->group)->toBeNull();
});
test('throws with empty message returns null message', function (): void {
    $attributes = $this->parser->parse('php throws(RuntimeException, "")');

    expect($attributes->attribute)->toBe(Attribute::Throws);
    expect($attributes->throwsClass)->toBe('RuntimeException');
    expect($attributes->throwsMessage)->toBeNull();
});
