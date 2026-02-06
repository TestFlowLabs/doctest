<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Executor\ExecutionResult;
use TestFlowLabs\DocTest\Assertion\AssertionResultDetail;

beforeEach(function (): void {
    $this->makeBlock = (fn (): CodeBlock => new CodeBlock(
        file: 'test.md',
        startLine: 1,
        rawCode: 'echo "Hi";',
        executableCode: 'echo "Hi";',
        attributes: new Attributes(),
        assertions: [],
    ));
});
test('construction with all properties', function (): void {
    $block  = ($this->makeBlock)();
    $result = new ExecutionResult(
        passed: true,
        codeBlock: $block,
        actualOutput: 'Hi',
        expectedOutput: 'Hi',
        diff: null,
        error: null,
        duration: 0.05,
    );

    expect($result->passed)->toBeTrue();
    expect($result->codeBlock)->toBe($block);
    expect($result->actualOutput)->toBe('Hi');
    expect($result->expectedOutput)->toBe('Hi');
    expect($result->diff)->toBeNull();
    expect($result->error)->toBeNull();
    expect($result->duration)->toBe(0.05);
});
test('defaults for optional params', function (): void {
    $result = new ExecutionResult(
        passed: true,
        codeBlock: ($this->makeBlock)(),
    );

    expect($result->actualOutput)->toBeNull();
    expect($result->expectedOutput)->toBeNull();
    expect($result->diff)->toBeNull();
    expect($result->error)->toBeNull();
    expect($result->duration)->toBe(0.0);
});
test('failed result with diff and error', function (): void {
    $result = new ExecutionResult(
        passed: false,
        codeBlock: ($this->makeBlock)(),
        actualOutput: 'wrong',
        expectedOutput: 'right',
        diff: '- right\n+ wrong',
        error: 'Output mismatch',
        duration: 0.1,
    );

    expect($result->passed)->toBeFalse();
    expect($result->actualOutput)->toBe('wrong');
    expect($result->expectedOutput)->toBe('right');
    expect($result->diff)->toBe('- right\n+ wrong');
    expect($result->error)->toBe('Output mismatch');
});
test('skipped result', function (): void {
    $result = new ExecutionResult(
        passed: true,
        codeBlock: ($this->makeBlock)(),
        skipped: true,
    );

    expect($result->passed)->toBeTrue();
    expect($result->skipped)->toBeTrue();
});
test('assertion details defaults to empty array', function (): void {
    $result = new ExecutionResult(
        passed: true,
        codeBlock: ($this->makeBlock)(),
    );

    expect($result->assertionDetails)->toBe([]);
});
test('stores assertion details', function (): void {
    $details = [
        new AssertionResultDetail(type: 'output', passed: true, expected: 'Hi', actual: 'Hi', line: 1),
        new AssertionResultDetail(type: 'result_comment', passed: true, expected: '42', actual: '42', line: 2, expression: '$x = 42'),
    ];

    $result = new ExecutionResult(
        passed: true,
        codeBlock: ($this->makeBlock)(),
        assertionDetails: $details,
    );

    expect($result->assertionDetails)->toHaveCount(2);
    expect($result->assertionDetails[0]->type)->toBe('output');
    expect($result->assertionDetails[1]->type)->toBe('result_comment');
    expect($result->assertionDetails[1]->expression)->toBe('$x = 42');
});
