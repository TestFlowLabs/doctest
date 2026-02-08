<?php

declare(strict_types=1);

use TestFlowLabs\DocTest\DocTest;
use TestFlowLabs\DocTest\Config\DocTestConfig;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    $this->output      = new BufferedOutput();
    $this->fixturesDir = dirname(__DIR__).'/Fixtures/bootstrap-profiles';

    $this->runDocTest = function (DocTestConfig $config): int {
        $docTest = new DocTest($config, $this->output);

        return $docTest->run();
    };

    $this->getOutput = (fn (): string => $this->output->fetch());
});

test('single bootstrap profile resolves and block passes', function (): void {
    $config = DocTestConfig::fromArray([
        'paths'          => [$this->fixturesDir.'/bootstrap-single.md'],
        'bootstraps_dir' => $this->fixturesDir.'/.doctest',
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});

test('composed bootstrap profiles both available in block', function (): void {
    $config = DocTestConfig::fromArray([
        'paths'          => [$this->fixturesDir.'/bootstrap-compose.md'],
        'bootstraps_dir' => $this->fixturesDir.'/.doctest',
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});

test('block without bootstrap attribute still works', function (): void {
    $config = DocTestConfig::fromArray([
        'paths'          => [$this->fixturesDir.'/bootstrap-mixed.md'],
        'bootstraps_dir' => $this->fixturesDir.'/.doctest',
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});

test('mixed file: some blocks with bootstrap, some without', function (): void {
    $config = DocTestConfig::fromArray([
        'paths'          => [$this->fixturesDir.'/bootstrap-mixed.md'],
        'bootstraps_dir' => $this->fixturesDir.'/.doctest',
    ]);

    $exitCode = ($this->runDocTest)($config);
    $output   = ($this->getOutput)();

    expect($exitCode)->toBe(0);
    expect(substr_count((string) $output, '✔'))->toBe(3);
});

test('unknown profile throws RuntimeException with available profiles', function (): void {
    $config = DocTestConfig::fromArray([
        'paths'          => [$this->fixturesDir.'/bootstrap-unknown.md'],
        'bootstraps_dir' => $this->fixturesDir.'/.doctest',
    ]);

    expect(fn () => ($this->runDocTest)($config))
        ->toThrow(RuntimeException::class, 'Unknown bootstrap profile "nonexistent"');
});

test('bootstrap with group attribute works together', function (): void {
    $config = DocTestConfig::fromArray([
        'paths'          => [$this->fixturesDir.'/bootstrap-group.md'],
        'bootstraps_dir' => $this->fixturesDir.'/.doctest',
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});

test('bootstrap with result comment assertion works', function (): void {
    $config = DocTestConfig::fromArray([
        'paths'          => [$this->fixturesDir.'/bootstrap-result-comment.md'],
        'bootstraps_dir' => $this->fixturesDir.'/.doctest',
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});

test('works without bootstraps_dir configured', function (): void {
    $config = DocTestConfig::fromArray([
        'paths'          => [$this->fixturesDir.'/bootstrap-mixed.md'],
        'bootstraps_dir' => '/nonexistent/path',
    ]);

    // Blocks without bootstrap should still pass, blocks with bootstrap fail
    // because resolver is not created when dir doesn't exist
    $exitCode = ($this->runDocTest)($config);

    // The block with bootstrap="math" will fail because the function isn't available
    expect($exitCode)->toBe(1);
});
