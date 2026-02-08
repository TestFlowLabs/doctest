<?php

declare(strict_types=1);

use TestFlowLabs\DocTest\Executor\WorkItem;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\Executor\WorkerPool;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Executor\ProcessResult;

function createWorkItem(int $index, string $code): WorkItem
{
    $filePath = tempnam(sys_get_temp_dir(), 'doctest_pool_').'.php';
    file_put_contents($filePath, "<?php\n".$code);

    return new WorkItem(
        index: $index,
        filePath: $filePath,
        codeBlock: new CodeBlock(
            file: 'test.md',
            startLine: $index * 10,
            rawCode: $code,
            executableCode: $code,
            attributes: new Attributes(),
            assertions: [],
        ),
    );
}

test('runs single work item and returns result', function (): void {
    $pool = new WorkerPool(maxWorkers: 1, timeout: 5, memoryLimit: '128M');
    $item = createWorkItem(0, 'echo "hello";');

    $results = $pool->run([$item]);

    expect($results)->toHaveKey(0);
    expect($results[0])->toBeInstanceOf(ProcessResult::class);
    expect($results[0]->stdout)->toBe('hello');
    expect($results[0]->exitCode)->toBe(0);

    @unlink($item->filePath);
});

test('runs multiple items concurrently', function (): void {
    $pool = new WorkerPool(maxWorkers: 3, timeout: 5, memoryLimit: '128M');

    $items = [
        createWorkItem(0, 'echo "a";'),
        createWorkItem(1, 'echo "b";'),
        createWorkItem(2, 'echo "c";'),
    ];

    $results = $pool->run($items);

    expect($results)->toHaveCount(3);
    expect($results[0]->stdout)->toBe('a');
    expect($results[1]->stdout)->toBe('b');
    expect($results[2]->stdout)->toBe('c');

    foreach ($items as $item) {
        @unlink($item->filePath);
    }
});

test('respects max workers limit', function (): void {
    $pool = new WorkerPool(maxWorkers: 2, timeout: 5, memoryLimit: '128M');

    $items = [
        createWorkItem(0, 'echo "x";'),
        createWorkItem(1, 'echo "y";'),
        createWorkItem(2, 'echo "z";'),
        createWorkItem(3, 'echo "w";'),
    ];

    $results = $pool->run($items);

    expect($results)->toHaveCount(4);
    expect($results[0]->exitCode)->toBe(0);
    expect($results[1]->exitCode)->toBe(0);
    expect($results[2]->exitCode)->toBe(0);
    expect($results[3]->exitCode)->toBe(0);

    foreach ($items as $item) {
        @unlink($item->filePath);
    }
});

test('results are keyed by work item index', function (): void {
    $pool = new WorkerPool(maxWorkers: 2, timeout: 5, memoryLimit: '128M');

    $items = [
        createWorkItem(5, 'echo "five";'),
        createWorkItem(10, 'echo "ten";'),
    ];

    $results = $pool->run($items);

    expect($results)->toHaveKey(5);
    expect($results)->toHaveKey(10);
    expect($results[5]->stdout)->toBe('five');
    expect($results[10]->stdout)->toBe('ten');

    foreach ($items as $item) {
        @unlink($item->filePath);
    }
});

test('captures stderr output', function (): void {
    $pool = new WorkerPool(maxWorkers: 1, timeout: 5, memoryLimit: '128M');
    $item = createWorkItem(0, 'fwrite(STDERR, "error info");');

    $results = $pool->run([$item]);

    expect($results[0]->stderr)->toBe('error info');

    @unlink($item->filePath);
});

test('captures non-zero exit code', function (): void {
    $pool = new WorkerPool(maxWorkers: 1, timeout: 5, memoryLimit: '128M');
    $item = createWorkItem(0, 'exit(42);');

    $results = $pool->run([$item]);

    expect($results[0]->exitCode)->toBe(42);

    @unlink($item->filePath);
});

test('handles empty items array', function (): void {
    $pool = new WorkerPool(maxWorkers: 2, timeout: 5, memoryLimit: '128M');

    $results = $pool->run([]);

    expect($results)->toBe([]);
});

test('records duration for each result', function (): void {
    $pool = new WorkerPool(maxWorkers: 1, timeout: 5, memoryLimit: '128M');
    $item = createWorkItem(0, 'echo "fast";');

    $results = $pool->run([$item]);

    expect($results[0]->duration)->toBeGreaterThan(0.0);

    @unlink($item->filePath);
});
