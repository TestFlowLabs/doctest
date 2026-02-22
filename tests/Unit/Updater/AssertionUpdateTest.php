<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Updater\AssertionUpdate;

test('constructs with all properties', function (): void {
    $update = new AssertionUpdate(
        markdownLine: 8,
        type: 'html_comment',
        assertionType: 'output',
        oldValue: 'wrong',
        newValue: 'hello',
    );

    expect($update->markdownLine)->toBe(8);
    expect($update->type)->toBe('html_comment');
    expect($update->assertionType)->toBe('output');
    expect($update->oldValue)->toBe('wrong');
    expect($update->newValue)->toBe('hello');
});
test('constructs result comment update', function (): void {
    $update = new AssertionUpdate(
        markdownLine: 13,
        type: 'result_comment',
        assertionType: 'result_comment',
        oldValue: '42',
        newValue: '43',
    );

    expect($update->markdownLine)->toBe(13);
    expect($update->type)->toBe('result_comment');
});
