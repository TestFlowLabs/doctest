<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\DocTest;
use TestFlowLabs\DocTest\Config\DocTestConfig;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    $this->output  = new BufferedOutput();
    $this->tempDir = sys_get_temp_dir().'/doctest_display_'.uniqid();
    mkdir($this->tempDir, 0777, true);
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

test('normal run ignores display output block', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '```php',
        'echo "hello";',
        '```',
        '<!-- doctest-output -->',
        '```',
        'old content',
        '```',
        '',
    ]));

    $config  = new DocTestConfig(paths: [$file]);
    $docTest = new DocTest($config, $this->output);

    // Should pass — display block is not an assertion
    expect($docTest->run())->toBe(0);
});
test('update refreshes display output block', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '```php',
        'echo "hello world";',
        '```',
        '<!-- doctest-output -->',
        '```',
        'old content',
        '```',
        '',
    ]));

    $config  = new DocTestConfig(paths: [$file], update: true);
    $docTest = new DocTest($config, $this->output);
    $docTest->run();

    $content = file_get_contents($file);
    expect($content)->toContain("```\nhello world\n```");
    expect($content)->not->toContain('old content');
});
test('update with lines option limits output', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, "```php\necho \"line1\" . \"\\n\" . \"line2\" . \"\\n\" . \"line3\";\n```\n<!-- doctest-output: lines=2 -->\n```\nold\n```\n");

    $config  = new DocTestConfig(paths: [$file], update: true);
    $docTest = new DocTest($config, $this->output);
    $docTest->run();

    $content = file_get_contents($file);
    // The display block should only have first 2 lines
    expect($content)->toContain("<!-- doctest-output: lines=2 -->\n```\nline1\nline2\n```");
});
test('update with tail option limits output', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, "```php\necho \"line1\" . \"\\n\" . \"line2\" . \"\\n\" . \"line3\";\n```\n<!-- doctest-output: tail=2 -->\n```\nold\n```\n");

    $config  = new DocTestConfig(paths: [$file], update: true);
    $docTest = new DocTest($config, $this->output);
    $docTest->run();

    $content = file_get_contents($file);
    // The display block should only have last 2 lines
    expect($content)->toContain("<!-- doctest-output: tail=2 -->\n```\nline2\nline3\n```");
});
test('display output with assertion both work', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '```php',
        'echo "hello";',
        '```',
        '<!-- doctest: hello -->',
        '<!-- doctest-output -->',
        '```',
        'old',
        '```',
        '',
    ]));

    $config  = new DocTestConfig(paths: [$file]);
    $docTest = new DocTest($config, $this->output);

    // Should pass — assertion matches, display block ignored
    expect($docTest->run())->toBe(0);
});
