<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Config\DocTestConfig;

test('uses defaults for missing keys', function (): void {
    $config = DocTestConfig::fromArray([]);

    expect($config->paths)->toBe(['docs', 'README.md']);
    expect($config->exclude)->toBe([]);
    expect($config->timeout)->toBe(30);
    expect($config->memoryLimit)->toBe('256M');
    expect($config->stopOnFailure)->toBeFalse();
    expect($config->verbosity)->toBe(0);
    expect($config->normalizeWhitespace)->toBeTrue();
    expect($config->trimTrailing)->toBeTrue();
});
test('loads from array with all keys', function (): void {
    $config = DocTestConfig::fromArray([
        'paths'     => ['src'],
        'exclude'   => ['docs/archive/*'],
        'execution' => [
            'timeout'         => 60,
            'memory_limit'    => '512M',
            'stop_on_failure' => true,
        ],
        'output' => [
            'normalize_whitespace' => false,
            'trim_trailing'        => false,
        ],
    ]);

    expect($config->paths)->toBe(['src']);
    expect($config->exclude)->toBe(['docs/archive/*']);
    expect($config->timeout)->toBe(60);
    expect($config->memoryLimit)->toBe('512M');
    expect($config->stopOnFailure)->toBeTrue();
    expect($config->normalizeWhitespace)->toBeFalse();
    expect($config->trimTrailing)->toBeFalse();
});
test('loads from php file', function (): void {
    $tmpFile = tempnam(sys_get_temp_dir(), 'doctest-config-');

    try {
        file_put_contents($tmpFile, "<?php\nreturn ['paths' => ['custom']];\n");

        $config = DocTestConfig::load($tmpFile);

        expect($config->paths)->toBe(['custom']);
    } finally {
        @unlink($tmpFile);
    }
});
test('returns default config when no file exists', function (): void {
    $config = DocTestConfig::load('/nonexistent/path/doctest.php');

    expect($config->paths)->toBe(['docs', 'README.md']);
});
test('config merges with defaults', function (): void {
    $config = DocTestConfig::fromArray([
        'execution' => ['timeout' => 120],
    ]);

    expect($config->timeout)->toBe(120);
    expect($config->memoryLimit)->toBe('256M');
    expect($config->stopOnFailure)->toBeFalse();
});
test('reporters config defaults', function (): void {
    $config = DocTestConfig::fromArray([]);

    expect($config->reporterConsole)->toBeTrue();
    expect($config->reporterJson)->toBeNull();
});
test('reporters config with file paths', function (): void {
    $config = DocTestConfig::fromArray([
        'reporters' => [
            'console' => true,
            'json'    => 'build/doctest.json',
        ],
    ]);

    expect($config->reporterConsole)->toBeTrue();
    expect($config->reporterJson)->toBe('build/doctest.json');
});
test('exclude patterns loaded', function (): void {
    $config = DocTestConfig::fromArray([
        'exclude' => ['docs/archive/*', 'docs/**/*draft*.md'],
    ]);

    expect($config->exclude)->toBe(['docs/archive/*', 'docs/**/*draft*.md']);
});
test('wrong type timeout falls back to default', function (): void {
    $config = DocTestConfig::fromArray([
        'execution' => ['timeout' => 'not_int'],
    ]);

    expect($config->timeout)->toBe(30);
});
test('wrong type memory limit falls back to default', function (): void {
    $config = DocTestConfig::fromArray([
        'execution' => ['memory_limit' => 42],
    ]);

    expect($config->memoryLimit)->toBe('256M');
});
test('load returns defaults when file returns non array', function (): void {
    $tmpFile = tempnam(sys_get_temp_dir(), 'doctest-config-');

    try {
        file_put_contents($tmpFile, "<?php\nreturn 'not an array';\n");

        $config = DocTestConfig::load($tmpFile);

        expect($config->paths)->toBe(['docs', 'README.md']);
    } finally {
        @unlink($tmpFile);
    }
});
test('stop on failure from top level key', function (): void {
    $config = DocTestConfig::fromArray([
        'stop_on_failure' => true,
    ]);

    expect($config->stopOnFailure)->toBeTrue();
});
test('filter and dry run loaded', function (): void {
    $config = DocTestConfig::fromArray([
        'filter'  => 'example.md',
        'dry_run' => true,
    ]);

    expect($config->filter)->toBe('example.md');
    expect($config->dryRun)->toBeTrue();
});
test('non array execution section uses defaults', function (): void {
    $config = DocTestConfig::fromArray([
        'execution' => 'invalid',
    ]);

    expect($config->timeout)->toBe(30);
    expect($config->memoryLimit)->toBe('256M');
});
test('bootstrap defaults to null', function (): void {
    $config = DocTestConfig::fromArray([]);

    expect($config->bootstrap)->toBeNull();
});
test('bootstrap loaded from array', function (): void {
    $config = DocTestConfig::fromArray([
        'bootstrap' => 'tests/bootstrap.php',
    ]);

    expect($config->bootstrap)->toBe('tests/bootstrap.php');
});
test('bootstrap non string falls back to null', function (): void {
    $config = DocTestConfig::fromArray([
        'bootstrap' => 42,
    ]);

    expect($config->bootstrap)->toBeNull();
});
test('bootstraps dir defaults to .doctest', function (): void {
    $config = DocTestConfig::fromArray([]);

    expect($config->bootstrapsDir)->toBe('.doctest');
});
test('bootstraps dir loaded from array', function (): void {
    $config = DocTestConfig::fromArray([
        'bootstraps_dir' => 'custom/bootstraps',
    ]);

    expect($config->bootstrapsDir)->toBe('custom/bootstraps');
});
test('bootstraps dir non string falls back to default', function (): void {
    $config = DocTestConfig::fromArray([
        'bootstraps_dir' => 42,
    ]);

    expect($config->bootstrapsDir)->toBe('.doctest');
});
test('bootstraps dir in constructor defaults to .doctest', function (): void {
    $config = new DocTestConfig();

    expect($config->bootstrapsDir)->toBe('.doctest');
});
test('parallel defaults to 1', function (): void {
    $config = DocTestConfig::fromArray([]);

    expect($config->parallel)->toBe(1);
});
test('parallel loaded from execution section', function (): void {
    $config = DocTestConfig::fromArray([
        'execution' => ['parallel' => 4],
    ]);

    expect($config->parallel)->toBe(4);
});
test('parallel non int falls back to default', function (): void {
    $config = DocTestConfig::fromArray([
        'execution' => ['parallel' => 'many'],
    ]);

    expect($config->parallel)->toBe(1);
});
test('parallel in constructor defaults to 1', function (): void {
    $config = new DocTestConfig();

    expect($config->parallel)->toBe(1);
});
test('blockIndices defaults to empty array', function (): void {
    $config = new DocTestConfig();

    expect($config->blockIndices)->toBe([]);
});
test('blockIndices accepts file to index map', function (): void {
    $config = new DocTestConfig(
        blockIndices: ['README.md' => 3, 'docs/api.md' => 1],
    );

    expect($config->blockIndices)->toBe(['README.md' => 3, 'docs/api.md' => 1]);
});
test('update defaults to false', function (): void {
    $config = new DocTestConfig();

    expect($config->update)->toBeFalse();
});
test('update accepts true', function (): void {
    $config = new DocTestConfig(update: true);

    expect($config->update)->toBeTrue();
});
test('update loaded from array', function (): void {
    $config = DocTestConfig::fromArray([
        'update' => true,
    ]);

    expect($config->update)->toBeTrue();
});
test('update defaults to false in fromArray', function (): void {
    $config = DocTestConfig::fromArray([]);

    expect($config->update)->toBeFalse();
});
