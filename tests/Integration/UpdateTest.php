<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\DocTest;
use TestFlowLabs\DocTest\Config\DocTestConfig;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    $this->output  = new BufferedOutput();
    $this->tempDir = sys_get_temp_dir().'/doctest_update_'.uniqid();
    mkdir($this->tempDir, 0777, true);

    $this->runUpdate = function (string $file): int {
        $config = new DocTestConfig(
            paths: [$file],
            update: true,
        );
        $docTest = new DocTest($config, $this->output);

        return $docTest->run();
    };

    $this->runNormal = function (string $file): int {
        $config = new DocTestConfig(
            paths: [$file],
        );
        $docTest = new DocTest($config, $this->output);

        return $docTest->run();
    };
});

afterEach(function (): void {
    $files = glob($this->tempDir.'/*');
    if (is_array($files)) {
        foreach ($files as $file) {
            unlink($file);
        }
    }
    rmdir($this->tempDir);
});

test('updates single output assertion with actual value', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '```php',
        'echo "hello";',
        '```',
        '<!-- doctest: wrong -->',
        '',
    ]));

    $exitCode = ($this->runUpdate)($file);

    expect($exitCode)->toBe(0);
    expect(file_get_contents($file))->toContain('<!-- doctest: hello -->');
});
test('updates result comment assertion', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '```php',
        '$x = 43; // => 42',
        '```',
        '',
    ]));

    $exitCode = ($this->runUpdate)($file);

    expect($exitCode)->toBe(0);
    expect(file_get_contents($file))->toContain('// => 43');
});
test('updates json assertion', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '```php',
        'echo json_encode(["a" => 1]);',
        '```',
        '<!-- doctest-json: {"old":true} -->',
        '',
    ]));

    $exitCode = ($this->runUpdate)($file);

    expect($exitCode)->toBe(0);
    expect(file_get_contents($file))->toContain('<!-- doctest-json: {"a":1} -->');
});
test('skips wildcard output assertion', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '```php',
        'echo "Generated: " . date("Y-m-d");',
        '```',
        '<!-- doctest: Generated: {{date}} -->',
        '',
    ]));

    $exitCode = ($this->runUpdate)($file);

    expect($exitCode)->toBe(0);
    // Wildcard assertion should NOT be changed
    expect(file_get_contents($file))->toContain('<!-- doctest: Generated: {{date}} -->');
});
test('skips contains assertion', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '```php',
        'echo "hello world";',
        '```',
        '<!-- doctest-contains: missing -->',
        '',
    ]));

    $exitCode = ($this->runUpdate)($file);

    // Contains is not updatable, so the failure persists
    expect($exitCode)->toBe(1);
    expect(file_get_contents($file))->toContain('<!-- doctest-contains: missing -->');
});
test('skips matches assertion', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '```php',
        'echo "hello";',
        '```',
        '<!-- doctest-matches: /^world$/ -->',
        '',
    ]));

    $exitCode = ($this->runUpdate)($file);

    // Matches is not updatable
    expect($exitCode)->toBe(1);
    expect(file_get_contents($file))->toContain('<!-- doctest-matches: /^world$/ -->');
});
test('code error does not update assertion', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '```php',
        'this is not valid php;',
        '```',
        '<!-- doctest: something -->',
        '',
    ]));

    $exitCode = ($this->runUpdate)($file);

    // Error in code → real failure, assertion not touched
    expect($exitCode)->toBe(1);
    expect(file_get_contents($file))->toContain('<!-- doctest: something -->');
});
test('updated file passes on re-run', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '```php',
        'echo "hello";',
        '```',
        '<!-- doctest: wrong -->',
        '',
    ]));

    ($this->runUpdate)($file);

    // Re-run normally — should pass
    $exitCode = ($this->runNormal)($file);
    expect($exitCode)->toBe(0);
});
test('updates multiple files', function (): void {
    $file1 = $this->tempDir.'/a.md';
    $file2 = $this->tempDir.'/b.md';

    file_put_contents($file1, implode("\n", [
        '```php',
        'echo "aaa";',
        '```',
        '<!-- doctest: wrong1 -->',
        '',
    ]));
    file_put_contents($file2, implode("\n", [
        '```php',
        'echo "bbb";',
        '```',
        '<!-- doctest: wrong2 -->',
        '',
    ]));

    $config = new DocTestConfig(
        paths: [$file1, $file2],
        update: true,
    );
    $docTest  = new DocTest($config, $this->output);
    $exitCode = $docTest->run();

    expect($exitCode)->toBe(0);
    expect(file_get_contents($file1))->toContain('<!-- doctest: aaa -->');
    expect(file_get_contents($file2))->toContain('<!-- doctest: bbb -->');
});
test('update output contains pencil symbol', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '```php',
        'echo "hello";',
        '```',
        '<!-- doctest: wrong -->',
        '',
    ]));

    ($this->runUpdate)($file);
    $display = $this->output->fetch();

    expect($display)->toContain('✎');
    expect($display)->toContain('Updated:');
});
test('update with no failures reports zero updates', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '```php',
        'echo "hello";',
        '```',
        '<!-- doctest: hello -->',
        '',
    ]));

    $exitCode = ($this->runUpdate)($file);

    expect($exitCode)->toBe(0);
    $display = $this->output->fetch();
    expect($display)->toContain('Updated: 0 assertions');
});
test('updates multi line output assertion', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '```php',
        'echo "line1\nline2";',
        '```',
        '<!-- doctest: wrong -->',
        '',
    ]));

    ($this->runUpdate)($file);

    $content = file_get_contents($file);
    expect($content)->toContain("<!-- doctest:\nline1\nline2\n-->");
});
