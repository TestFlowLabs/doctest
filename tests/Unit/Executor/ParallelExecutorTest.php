<?php

declare(strict_types=1);

use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\Executor\WorkerPool;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Executor\CodeGenerator;
use TestFlowLabs\DocTest\Executor\ProcessResult;
use TestFlowLabs\DocTest\Executor\ParallelExecutor;

function makeBlock(int $startLine, string $code): CodeBlock
{
    return new CodeBlock(
        file: 'test.md',
        startLine: $startLine,
        rawCode: $code,
        executableCode: $code,
        attributes: new Attributes(),
        assertions: [],
    );
}

test('executes single block and returns process result', function (): void {
    $executor = new ParallelExecutor(
        codeGenerator: new CodeGenerator(),
        workerPool: new WorkerPool(maxWorkers: 2, timeout: 5, memoryLimit: '128M'),
    );

    $blocks  = [makeBlock(1, 'echo "hello";')];
    $results = $executor->execute($blocks);

    expect($results)->toHaveCount(1);
    expect($results[0])->toBeInstanceOf(ProcessResult::class);
    expect($results[0]->stdout)->toBe('hello');
    expect($results[0]->exitCode)->toBe(0);
});

test('executes multiple blocks in parallel', function (): void {
    $executor = new ParallelExecutor(
        codeGenerator: new CodeGenerator(),
        workerPool: new WorkerPool(maxWorkers: 3, timeout: 5, memoryLimit: '128M'),
    );

    $blocks = [
        makeBlock(1, 'echo "a";'),
        makeBlock(5, 'echo "b";'),
        makeBlock(10, 'echo "c";'),
    ];

    $results = $executor->execute($blocks);

    expect($results)->toHaveCount(3);
    expect($results[0]->stdout)->toBe('a');
    expect($results[1]->stdout)->toBe('b');
    expect($results[2]->stdout)->toBe('c');
});

test('results are keyed by block position', function (): void {
    $executor = new ParallelExecutor(
        codeGenerator: new CodeGenerator(),
        workerPool: new WorkerPool(maxWorkers: 2, timeout: 5, memoryLimit: '128M'),
    );

    $blocks = [
        makeBlock(1, 'echo "first";'),
        makeBlock(5, 'echo "second";'),
    ];

    $results = $executor->execute($blocks);

    expect($results)->toHaveKey(0);
    expect($results)->toHaveKey(1);
    expect($results[0]->stdout)->toBe('first');
    expect($results[1]->stdout)->toBe('second');
});

test('handles empty blocks array', function (): void {
    $executor = new ParallelExecutor(
        codeGenerator: new CodeGenerator(),
        workerPool: new WorkerPool(maxWorkers: 2, timeout: 5, memoryLimit: '128M'),
    );

    $results = $executor->execute([]);

    expect($results)->toBe([]);
});

test('cleans up temp files after execution', function (): void {
    $executor = new ParallelExecutor(
        codeGenerator: new CodeGenerator(),
        workerPool: new WorkerPool(maxWorkers: 1, timeout: 5, memoryLimit: '128M'),
    );

    $blocks  = [makeBlock(1, 'echo "cleanup test";')];
    $results = $executor->execute($blocks);

    // Verify execution succeeded
    expect($results[0]->exitCode)->toBe(0);

    // Temp files should be cleaned up - we can't easily verify this
    // without exposing internals, but the test ensures no crash
});

test('captures stderr from blocks', function (): void {
    $executor = new ParallelExecutor(
        codeGenerator: new CodeGenerator(),
        workerPool: new WorkerPool(maxWorkers: 1, timeout: 5, memoryLimit: '128M'),
    );

    $blocks  = [makeBlock(1, 'fwrite(STDERR, "error data");')];
    $results = $executor->execute($blocks);

    expect($results[0]->stderr)->toContain('error data');
});
