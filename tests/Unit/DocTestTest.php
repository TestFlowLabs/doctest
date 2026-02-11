<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\DocTest;
use TestFlowLabs\DocTest\Config\DocTestConfig;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    $this->output      = new BufferedOutput();
    $this->fixturesDir = dirname(__DIR__).'/Fixtures';

    $this->makeDocTest = (fn (DocTestConfig $config): DocTest => new DocTest($config, $this->output));
});
test('run returns 0 when all blocks pass', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/basic.md'],
    ]);

    $docTest  = ($this->makeDocTest)($config);
    $exitCode = $docTest->run();

    expect($exitCode)->toBe(0);
});
test('run returns 1 when any block fails', function (): void {
    // Create a temp file with a failing assertion (HTML comment syntax)
    $tempFile = sys_get_temp_dir().'/doctest_failing_'.uniqid().'.md';
    file_put_contents($tempFile, "```php\necho \"wrong\";\n```\n<!-- doctest: right -->\n");

    $config = DocTestConfig::fromArray([
        'paths' => [$tempFile],
    ]);

    $docTest  = ($this->makeDocTest)($config);
    $exitCode = $docTest->run();

    unlink($tempFile);
    expect($exitCode)->toBe(1);
});
test('run returns 3 when no tests found', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir.'/no-php.md'],
    ]);

    $docTest  = ($this->makeDocTest)($config);
    $exitCode = $docTest->run();

    expect($exitCode)->toBe(3);
});
test('respects dry run', function (): void {
    $config = DocTestConfig::fromArray([
        'paths'   => [$this->fixturesDir.'/basic.md'],
        'dry_run' => true,
    ]);

    $docTest  = ($this->makeDocTest)($config);
    $exitCode = $docTest->run();

    $output = $this->output->fetch();

    expect($exitCode)->toBe(0);
    $this->assertStringContainsString('basic.md', $output);
});
test('respects stop on failure', function (): void {
    // Create a file with a failing block followed by a passing block (HTML comment syntax)
    $tempFile = sys_get_temp_dir().'/doctest_stop_'.uniqid().'.md';
    file_put_contents($tempFile, "```php\necho \"wrong\";\n```\n<!-- doctest: right -->\n\n```php\necho \"ok\";\n```\n<!-- doctest: ok -->\n");

    $config = DocTestConfig::fromArray([
        'paths'           => [$tempFile],
        'stop_on_failure' => true,
    ]);

    $docTest  = ($this->makeDocTest)($config);
    $exitCode = $docTest->run();

    unlink($tempFile);
    expect($exitCode)->toBe(1);
});
test('file returns execution results', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir],
    ]);

    $docTest = ($this->makeDocTest)($config);
    $results = $docTest->testFile($this->fixturesDir.'/basic.md');

    expect($results)->not->toBeEmpty();
    expect($results)->toHaveCount(4);
    // 4 PHP blocks in basic.md
});
test('all processes all discovered files', function (): void {
    $config = DocTestConfig::fromArray([
        'paths' => [$this->fixturesDir],
    ]);

    $docTest = ($this->makeDocTest)($config);
    $results = $docTest->testAll();

    // Should have results from multiple fixture files
    expect($results)->not->toBeEmpty();
});
test('run with block index filters to specific block', function (): void {
    $file   = $this->fixturesDir.'/basic.md';
    $config = new DocTestConfig(
        paths: [$file],
        blockIndices: [$file => 2],
    );

    $docTest  = ($this->makeDocTest)($config);
    $exitCode = $docTest->run();

    $output = $this->output->fetch();

    expect($exitCode)->toBe(0);
    // Block 2 in basic.md is: $x = 42; echo $x;
    $this->assertStringContainsString('42', $output);
    // Should only run 1 block
    expect(substr_count((string) $output, '✔'))->toBe(1);
});
test('run with out of range block index returns exit code 3', function (): void {
    $file   = $this->fixturesDir.'/basic.md';
    $config = new DocTestConfig(
        paths: [$file],
        blockIndices: [$file => 999],
    );

    $docTest  = ($this->makeDocTest)($config);
    $exitCode = $docTest->run();

    expect($exitCode)->toBe(3);
});
test('run with block index 0 returns exit code 3', function (): void {
    $file   = $this->fixturesDir.'/basic.md';
    $config = new DocTestConfig(
        paths: [$file],
        blockIndices: [$file => 0],
    );

    $docTest  = ($this->makeDocTest)($config);
    $exitCode = $docTest->run();

    expect($exitCode)->toBe(3);
});
