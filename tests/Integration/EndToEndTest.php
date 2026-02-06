<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\DocTest;
use TestFlowLabs\DocTest\Config\DocTestConfig;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

beforeEach(function (): void {
    $this->output      = new BufferedOutput();
    $this->fixturesDir = dirname(__DIR__).'/Fixtures';

    $this->runDocTest = function (DocTestConfig $config): int {
        $docTest = new DocTest($config, $this->output);

        return $docTest->run();
    };

    $this->getOutput = (fn (): string => $this->output->fetch());
});
test('runs simple output test and passes', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/basic.md'],
    ]);

    expect(($this->runDocTest)($config))->toBe(0);
});
test('runs failing output test and reports failure', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/failing-output.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);
    $output   = ($this->getOutput)();

    expect($exitCode)->toBe(1);
    $this->assertStringContainsString('✖', $output);
});
test('runs ignored block and skips it', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/ignore-block.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);
    $output   = ($this->getOutput)();

    expect($exitCode)->toBe(0);
    $this->assertStringContainsString('⊘', $output);
    $this->assertStringContainsString('✔', $output);
});
test('runs expect assertions', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/expect.md'],
    ]);

    expect(($this->runDocTest)($config))->toBe(0);
});
test('runs multiple files', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [
            $this->fixturesDir.'/basic.md',
            $this->fixturesDir.'/expect.md',
        ],
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});
test('respects dry run', function (): void {
    $config = DocTestConfig::fromArray([
        'paths'   => [$this->fixturesDir.'/basic.md'],
        'dry_run' => true,
    ]);

    $exitCode = ($this->runDocTest)($config);
    $output   = ($this->getOutput)();

    expect($exitCode)->toBe(0);
    $this->assertStringContainsString('⊘', $output);
});
test('respects stop on failure', function (): void {
    $tempFile = sys_get_temp_dir().'/doctest_stop_e2e_'.uniqid().'.md';
    file_put_contents($tempFile, "```php\necho \"wrong\";\n```\n<!-- doctest: right -->\n\n```php\necho \"ok\";\n```\n<!-- doctest: ok -->\n");

    try {
        $config = DocTestConfig::fromArray([
            'paths'           => [$tempFile],
            'stop_on_failure' => true,
        ]);

        $exitCode = ($this->runDocTest)($config);
        $output   = ($this->getOutput)();

        expect($exitCode)->toBe(1);
        // Should only have one FAIL, not a second PASS (stopped early)
        expect(substr_count((string) $output, '✖'))->toBe(1);
        $this->assertStringNotContainsString('✔', $output);
    } finally {
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
});
test('exit code 0 when all pass', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/basic.md'],
    ]);

    expect(($this->runDocTest)($config))->toBe(0);
});
test('exit code 1 when any fail', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/failing-output.md'],
    ]);

    expect(($this->runDocTest)($config))->toBe(1);
});
test('exit code 3 when no tests found', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/no-php.md'],
    ]);

    expect(($this->runDocTest)($config))->toBe(3);
});
test('handles empty markdown file', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/empty.md'],
    ]);

    expect(($this->runDocTest)($config))->toBe(3);
});
test('handles markdown with no php blocks', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/no-php.md'],
    ]);

    expect(($this->runDocTest)($config))->toBe(3);
});
test('runs result comment assertions', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/result-comment.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});
test('verbose shows assertion details for result comment', function (): void {
    $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/result-comment.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);
    $output   = ($this->getOutput)();

    expect($exitCode)->toBe(0);
    $this->assertStringContainsString('=> 42', $output);
    $this->assertStringContainsString('$x = 42', $output);
});
test('verbose shows assertion details for output', function (): void {
    $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/basic.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);
    $output   = ($this->getOutput)();

    expect($exitCode)->toBe(0);
    $this->assertStringContainsString('output:', $output);
});
test('normal verbosity hides assertion details', function (): void {
    $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/result-comment.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);
    $output   = ($this->getOutput)();

    expect($exitCode)->toBe(0);

    // Verbose detail lines are indented with 7 spaces + icon
    $this->assertStringNotContainsString('       ✔', $output);
});
test('verbose shows assertion details on failure', function (): void {
    $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/failing-output.md'],
    ]);

    $exitCode = ($this->runDocTest)($config);
    $output   = ($this->getOutput)();

    expect($exitCode)->toBe(1);
    $this->assertStringContainsString('✖', $output);
});
