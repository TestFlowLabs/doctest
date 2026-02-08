<?php

declare(strict_types=1);

use TestFlowLabs\DocTest\CodeBlock\Attribute;
use TestFlowLabs\DocTest\Parser\HtmlCommentAttributeParser;

beforeEach(function (): void {
    $this->parser = new HtmlCommentAttributeParser();
});

test('returns null for non-matching HTML comment', function (): void {
    $result = $this->parser->parse('<!-- doctest: expected -->');

    expect($result)->toBeNull();
});

test('returns null for empty string', function (): void {
    $result = $this->parser->parse('');

    expect($result)->toBeNull();
});

test('returns null for non-HTML content', function (): void {
    $result = $this->parser->parse('some random text');

    expect($result)->toBeNull();
});

test('parses ignore attribute', function (): void {
    $result = $this->parser->parse('<!-- doctest-attr: ignore -->');

    expect($result)->not->toBeNull();
    expect($result->attribute)->toBe(Attribute::Ignore);
});

test('parses no_run attribute', function (): void {
    $result = $this->parser->parse('<!-- doctest-attr: no_run -->');

    expect($result)->not->toBeNull();
    expect($result->attribute)->toBe(Attribute::NoRun);
});

test('parses setup attribute', function (): void {
    $result = $this->parser->parse('<!-- doctest-attr: setup -->');

    expect($result)->not->toBeNull();
    expect($result->attribute)->toBe(Attribute::Setup);
});

test('parses teardown attribute', function (): void {
    $result = $this->parser->parse('<!-- doctest-attr: teardown -->');

    expect($result)->not->toBeNull();
    expect($result->attribute)->toBe(Attribute::Teardown);
});

test('parses parse_error attribute', function (): void {
    $result = $this->parser->parse('<!-- doctest-attr: parse_error -->');

    expect($result)->not->toBeNull();
    expect($result->attribute)->toBe(Attribute::ParseError);
});

test('parses bare throws attribute', function (): void {
    $result = $this->parser->parse('<!-- doctest-attr: throws -->');

    expect($result)->not->toBeNull();
    expect($result->attribute)->toBe(Attribute::Throws);
    expect($result->throwsClass)->toBeNull();
    expect($result->throwsMessage)->toBeNull();
});

test('parses throws with class', function (): void {
    $result = $this->parser->parse('<!-- doctest-attr: throws(InvalidArgumentException) -->');

    expect($result)->not->toBeNull();
    expect($result->attribute)->toBe(Attribute::Throws);
    expect($result->throwsClass)->toBe('InvalidArgumentException');
    expect($result->throwsMessage)->toBeNull();
});

test('parses throws with class and message', function (): void {
    $result = $this->parser->parse('<!-- doctest-attr: throws(RuntimeException, "not found") -->');

    expect($result)->not->toBeNull();
    expect($result->attribute)->toBe(Attribute::Throws);
    expect($result->throwsClass)->toBe('RuntimeException');
    expect($result->throwsMessage)->toBe('not found');
});

test('parses throws with namespaced class', function (): void {
    $result = $this->parser->parse('<!-- doctest-attr: throws(App\\Exceptions\\ValidationException) -->');

    expect($result)->not->toBeNull();
    expect($result->throwsClass)->toBe('App\\Exceptions\\ValidationException');
});

test('parses group attribute', function (): void {
    $result = $this->parser->parse('<!-- doctest-attr: group="order-flow" -->');

    expect($result)->not->toBeNull();
    expect($result->group)->toBe('order-flow');
});

test('parses single bootstrap profile', function (): void {
    $result = $this->parser->parse('<!-- doctest-attr: bootstrap="laravel" -->');

    expect($result)->not->toBeNull();
    expect($result->bootstraps)->toBe(['laravel']);
});

test('parses multiple bootstrap profiles', function (): void {
    $result = $this->parser->parse('<!-- doctest-attr: bootstrap="laravel,database" -->');

    expect($result)->not->toBeNull();
    expect($result->bootstraps)->toBe(['laravel', 'database']);
});

test('parses bootstrap with spaces around names', function (): void {
    $result = $this->parser->parse('<!-- doctest-attr: bootstrap="laravel , db" -->');

    expect($result)->not->toBeNull();
    expect($result->bootstraps)->toBe(['laravel', 'db']);
});

test('parses combined attributes: group + setup', function (): void {
    $result = $this->parser->parse('<!-- doctest-attr: group="db" setup -->');

    expect($result)->not->toBeNull();
    expect($result->group)->toBe('db');
    expect($result->attribute)->toBe(Attribute::Setup);
});

test('parses combined attributes: group + bootstrap', function (): void {
    $result = $this->parser->parse('<!-- doctest-attr: group="auth" bootstrap="laravel" -->');

    expect($result)->not->toBeNull();
    expect($result->group)->toBe('auth');
    expect($result->bootstraps)->toBe(['laravel']);
});

test('parses combined attributes: group + throws + bootstrap', function (): void {
    $result = $this->parser->parse('<!-- doctest-attr: group="err" throws(RuntimeException) bootstrap="laravel" -->');

    expect($result)->not->toBeNull();
    expect($result->group)->toBe('err');
    expect($result->attribute)->toBe(Attribute::Throws);
    expect($result->throwsClass)->toBe('RuntimeException');
    expect($result->bootstraps)->toBe(['laravel']);
});

test('handles extra whitespace gracefully', function (): void {
    $result = $this->parser->parse('<!--   doctest-attr:   ignore   -->');

    expect($result)->not->toBeNull();
    expect($result->attribute)->toBe(Attribute::Ignore);
});

test('returns null for doctest-attr with empty content', function (): void {
    $result = $this->parser->parse('<!-- doctest-attr: -->');

    expect($result)->not->toBeNull();
    expect($result->attribute)->toBeNull();
    expect($result->group)->toBeNull();
    expect($result->bootstraps)->toBe([]);
});
