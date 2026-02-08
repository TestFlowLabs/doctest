<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\DocTest;
use TestFlowLabs\DocTest\Config\DocTestConfig;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    $this->output      = new BufferedOutput();
    $this->fixturesDir = dirname(__DIR__).'/Fixtures/debug-dump';

    $this->runDocTest = function (DocTestConfig $config): int {
        $docTest = new DocTest($config, $this->output);

        return $docTest->run();
    };

    $this->getOutput = (fn (): string => $this->output->fetch());
});
test('single dd passes and shows debug output', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/single.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);
    $output   = ($this->getOutput)();

    expect($exitCode)->toBe(0);
    $this->assertStringContainsString('dd', $output);
});
test('multiple dd dumps all pass', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/multiple.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});
test('dd mixed with result comment assertion both work', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/mixed-with-assertion.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});
test('dd mixed with output assertion both work', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/mixed-with-output.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});
test('dd in group blocks works with shared state', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/group.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});
test('dd with various expression types passes', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/expressions.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});
