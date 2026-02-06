<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\CodeBlock\Attribute;

test('from valid string returns case', function (): void {
    expect(Attribute::from('ignore'))->toBe(Attribute::Ignore);
    expect(Attribute::from('no_run'))->toBe(Attribute::NoRun);
    expect(Attribute::from('throws'))->toBe(Attribute::Throws);
    expect(Attribute::from('parse_error'))->toBe(Attribute::ParseError);
    expect(Attribute::from('setup'))->toBe(Attribute::Setup);
    expect(Attribute::from('teardown'))->toBe(Attribute::Teardown);
});
test('try from invalid string returns null', function (): void {
    expect(Attribute::tryFrom('invalid'))->toBeNull();
    expect(Attribute::tryFrom(''))->toBeNull();
});
