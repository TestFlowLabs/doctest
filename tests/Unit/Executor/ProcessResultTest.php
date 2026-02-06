<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Executor\ProcessResult;

test('construction with all properties', function (): void {
    $result = new ProcessResult(
        stdout: 'Hello World',
        stderr: '{"results":[]}',
        exitCode: 0,
        duration: 1.23,
    );

    expect($result->stdout)->toBe('Hello World');
    expect($result->stderr)->toBe('{"results":[]}');
    expect($result->exitCode)->toBe(0);
    expect($result->duration)->toBe(1.23);
});
test('with empty stdout and stderr', function (): void {
    $result = new ProcessResult(
        stdout: '',
        stderr: '',
        exitCode: 0,
        duration: 0.01,
    );

    expect($result->stdout)->toBe('');
    expect($result->stderr)->toBe('');
});
test('with nonzero exit code', function (): void {
    $result = new ProcessResult(
        stdout: '',
        stderr: 'Fatal error',
        exitCode: 255,
        duration: 0.5,
    );

    expect($result->exitCode)->toBe(255);
});
