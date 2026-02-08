<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\CodeBlock\Attribute;
use TestFlowLabs\DocTest\CodeBlock\Attributes;

test('construction with defaults', function (): void {
    $attributes = new Attributes();

    expect($attributes->attribute)->toBeNull();
    expect($attributes->throwsClass)->toBeNull();
    expect($attributes->throwsMessage)->toBeNull();
    expect($attributes->group)->toBeNull();
});
test('is ignore returns true for ignore attribute', function (): void {
    $attributes = new Attributes(attribute: Attribute::Ignore);

    expect($attributes->isIgnore())->toBeTrue();
});
test('is ignore returns false for other attribute', function (): void {
    $attributes = new Attributes(attribute: Attribute::Throws);

    expect($attributes->isIgnore())->toBeFalse();
});
test('is throws returns true for throws attribute', function (): void {
    $attributes = new Attributes(attribute: Attribute::Throws);

    expect($attributes->isThrows())->toBeTrue();
});
test('throws with class and message', function (): void {
    $attributes = new Attributes(
        attribute: Attribute::Throws,
        throwsClass: 'InvalidArgumentException',
        throwsMessage: 'Bad input',
    );

    expect($attributes->isThrows())->toBeTrue();
    expect($attributes->throwsClass)->toBe('InvalidArgumentException');
    expect($attributes->throwsMessage)->toBe('Bad input');
});
test('has group returns true when group set', function (): void {
    $attributes = new Attributes(group: 'order-flow');

    expect($attributes->hasGroup())->toBeTrue();
    expect($attributes->group)->toBe('order-flow');
});
test('has group returns false when no group', function (): void {
    $attributes = new Attributes();

    expect($attributes->hasGroup())->toBeFalse();
});
test('is setup returns true for setup attribute', function (): void {
    $attributes = new Attributes(attribute: Attribute::Setup);

    expect($attributes->isSetup())->toBeTrue();
});
test('is teardown returns true for teardown attribute', function (): void {
    $attributes = new Attributes(attribute: Attribute::Teardown);

    expect($attributes->isTeardown())->toBeTrue();
});
test('is no run returns true for no run attribute', function (): void {
    $attributes = new Attributes(attribute: Attribute::NoRun);

    expect($attributes->isNoRun())->toBeTrue();
});
test('is parse error returns true for parse error attribute', function (): void {
    $attributes = new Attributes(attribute: Attribute::ParseError);

    expect($attributes->isParseError())->toBeTrue();
});
test('bootstraps defaults to empty array', function (): void {
    $attributes = new Attributes();

    expect($attributes->bootstraps)->toBe([]);
});
test('has bootstraps returns true when bootstraps set', function (): void {
    $attributes = new Attributes(bootstraps: ['laravel']);

    expect($attributes->hasBootstraps())->toBeTrue();
    expect($attributes->bootstraps)->toBe(['laravel']);
});
test('has bootstraps returns false when empty', function (): void {
    $attributes = new Attributes();

    expect($attributes->hasBootstraps())->toBeFalse();
});
test('multiple bootstraps stored in order', function (): void {
    $attributes = new Attributes(bootstraps: ['laravel', 'database']);

    expect($attributes->bootstraps)->toBe(['laravel', 'database']);
});
