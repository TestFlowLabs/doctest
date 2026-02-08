<?php

declare(strict_types=1);

use TestFlowLabs\DocTest\DocTest;
use TestFlowLabs\DocTest\Config\DocTestConfig;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    $this->output      = new BufferedOutput();
    $this->fixturesDir = dirname(__DIR__).'/Fixtures';

    $this->runParallel = function (array $paths, int $workers = 2, bool $stopOnFailure = false): int {
        $config = DocTestConfig::fromArray([
            'paths'           => $paths,
            'execution'       => ['parallel' => $workers],
            'stop_on_failure' => $stopOnFailure,
        ]);

        $docTest = new DocTest($config, $this->output);

        return $docTest->run();
    };

    $this->getOutput = (fn (): string => $this->output->fetch());
});

test('parallel execution passes for basic fixture', function (): void {
    $exitCode = ($this->runParallel)([$this->fixturesDir.'/basic.md']);

    expect($exitCode)->toBe(0);
});

test('parallel execution detects failure', function (): void {
    $exitCode = ($this->runParallel)([$this->fixturesDir.'/failing-output.md']);
    $output   = ($this->getOutput)();

    expect($exitCode)->toBe(1);
    $this->assertStringContainsString('✖', $output);
});

test('parallel execution handles multiple files', function (): void {
    $exitCode = ($this->runParallel)([
        $this->fixturesDir.'/basic.md',
        $this->fixturesDir.'/ignore-block.md',
    ]);

    expect($exitCode)->toBe(0);
});

test('parallel execution with stop on failure stops early', function (): void {
    $exitCode = ($this->runParallel)(
        [$this->fixturesDir.'/failing-output.md'],
        workers: 2,
        stopOnFailure: true,
    );

    expect($exitCode)->toBe(1);
});

test('parallel execution skips ignored blocks', function (): void {
    $exitCode = ($this->runParallel)([$this->fixturesDir.'/ignore-block.md']);
    $output   = ($this->getOutput)();

    expect($exitCode)->toBe(0);
    $this->assertStringContainsString('⊘', $output);
});

test('parallel with 1 worker behaves like sequential', function (): void {
    $exitCode = ($this->runParallel)([$this->fixturesDir.'/basic.md'], workers: 1);

    expect($exitCode)->toBe(0);
});
