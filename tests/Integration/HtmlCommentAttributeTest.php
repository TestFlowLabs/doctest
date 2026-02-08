<?php

declare(strict_types=1);

use TestFlowLabs\DocTest\DocTest;
use TestFlowLabs\DocTest\Config\DocTestConfig;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    $this->output      = new BufferedOutput();
    $this->fixturesDir = dirname(__DIR__).'/Fixtures/html-comment-attrs';

    $this->runDocTest = function (DocTestConfig $config): int {
        $docTest = new DocTest($config, $this->output);

        return $docTest->run();
    };

    $this->getOutput = (fn (): string => $this->output->fetch());
});

test('ignore via HTML comment attribute', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/ignore.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);
    $output   = ($this->getOutput)();

    expect($exitCode)->toBe(0);
    $this->assertStringContainsString('⊘', $output);
});

test('no_run via HTML comment attribute', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/no-run.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});

test('throws via HTML comment attribute', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/throws.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});

test('group via HTML comment attribute', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/group.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});

test('setup and teardown via HTML comment attribute', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/setup-teardown.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});

test('parse_error via HTML comment attribute', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/parse-error.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});

test('combined HTML comment and info string attributes in same file', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/combined.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});

test('bootstrap via HTML comment attribute', function (): void {
    $config = DocTestConfig::fromArray([
        'paths'          => [$this->fixturesDir.'/output.md'],
        'bootstraps_dir' => dirname(__DIR__).'/Fixtures/bootstrap-profiles/.doctest',
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});

test('single doctest-attr comment is applied to block', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/multiple-comments.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});

test('multiple doctest-attr comments before block throws RuntimeException', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [dirname(__DIR__).'/FixturesErrors/html-comment-attrs/multiple-comments.md'],
    ]);

    expect(fn () => ($this->runDocTest)($config))
        ->toThrow(RuntimeException::class, 'Multiple');
});

test('text between comment and block breaks adjacency', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/text-between.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);
    $output   = ($this->getOutput)();

    expect($exitCode)->toBe(0);
    // Should NOT be skipped — the text paragraph cleared pending HTML blocks
    $this->assertStringNotContainsString('⊘', $output);
});

test('malformed doctest-attr comment is ignored', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/malformed.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});

test('conflicting attributes from both sources throws RuntimeException', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [dirname(__DIR__).'/FixturesErrors/html-comment-attrs/conflict.md'],
    ]);

    expect(fn () => ($this->runDocTest)($config))
        ->toThrow(RuntimeException::class, 'Conflicting');
});
