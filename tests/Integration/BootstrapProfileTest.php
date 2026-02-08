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

// --- Edge case tests ---

test('bootstrap profile that defines a class makes it available in block', function (): void {
    $config = DocTestConfig::fromArray([
        'paths'          => [$this->fixturesDir.'/bootstrap-class.md'],
        'bootstraps_dir' => $this->fixturesDir.'/.doctest',
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});

test('bootstrap profile with side effects applies them to block', function (): void {
    $config = DocTestConfig::fromArray([
        'paths'          => [$this->fixturesDir.'/bootstrap-side-effects.md'],
        'bootstraps_dir' => $this->fixturesDir.'/.doctest',
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});

test('multiple blocks with same bootstrap profile all pass', function (): void {
    $config = DocTestConfig::fromArray([
        'paths'          => [$this->fixturesDir.'/bootstrap-same-profile-twice.md'],
        'bootstraps_dir' => $this->fixturesDir.'/.doctest',
    ]);

    $exitCode = ($this->runDocTest)($config);
    $output   = ($this->getOutput)();

    expect($exitCode)->toBe(0);
    expect(substr_count((string) $output, '✔'))->toBe(2);
});

test('bootstrap with group and setup/teardown all compose correctly', function (): void {
    $config = DocTestConfig::fromArray([
        'paths'          => [$this->fixturesDir.'/bootstrap-with-setup-teardown.md'],
        'bootstraps_dir' => $this->fixturesDir.'/.doctest',
    ]);

    $exitCode = ($this->runDocTest)($config);

    expect($exitCode)->toBe(0);
});

test('bootstrap profile with syntax error reports failure', function (): void {
    $tmpDir     = sys_get_temp_dir().'/doctest-edge-'.bin2hex(random_bytes(4));
    $profileDir = $tmpDir.'/.doctest';
    mkdir($profileDir, 0755, true);
    file_put_contents($profileDir.'/broken.php', "<?php\nfunction broken( {");

    $mdFile = $tmpDir.'/test.md';
    file_put_contents($mdFile, "```php bootstrap=\"broken\"\necho 'hi';\n```\n<!-- doctest: hi -->\n");

    try {
        $config = DocTestConfig::fromArray([
            'paths'          => [$mdFile],
            'bootstraps_dir' => $profileDir,
        ]);

        $exitCode = ($this->runDocTest)($config);

        expect($exitCode)->toBe(1);
    } finally {
        @unlink($mdFile);
        @unlink($profileDir.'/broken.php');
        @rmdir($profileDir);
        @rmdir($tmpDir);
    }
});
