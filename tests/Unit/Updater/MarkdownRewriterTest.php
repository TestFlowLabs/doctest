<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Updater\AssertionUpdate;
use TestFlowLabs\DocTest\Updater\MarkdownRewriter;

beforeEach(function (): void {
    $this->rewriter = new MarkdownRewriter();
    $this->tempDir  = sys_get_temp_dir().'/doctest_rewriter_'.uniqid();
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

test('updates single line html comment assertion', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '# Test',
        '',
        '```php',
        'echo "hello";',
        '```',
        '<!-- doctest: wrong -->',
        '',
    ]));

    $updates = [
        new AssertionUpdate(
            markdownLine: 6,
            type: 'html_comment',
            assertionType: 'output',
            oldValue: 'wrong',
            newValue: 'hello',
        ),
    ];

    $count = $this->rewriter->rewrite($file, $updates);

    expect($count)->toBe(1);
    expect(file_get_contents($file))->toContain('<!-- doctest: hello -->');
    expect(file_get_contents($file))->not->toContain('wrong');
});
test('updates multi line html comment assertion', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '# Test',
        '',
        '```php',
        'echo "line1\nline2";',
        '```',
        '<!-- doctest:',
        'old1',
        'old2',
        '-->',
        '',
    ]));

    $updates = [
        new AssertionUpdate(
            markdownLine: 6,
            type: 'html_comment',
            assertionType: 'output',
            oldValue: "old1\nold2",
            newValue: "line1\nline2",
        ),
    ];

    $count = $this->rewriter->rewrite($file, $updates);

    expect($count)->toBe(1);
    $content = file_get_contents($file);
    expect($content)->toContain("<!-- doctest:\nline1\nline2\n-->");
    expect($content)->not->toContain('old1');
});
test('updates result comment assertion', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '# Test',
        '',
        '```php',
        '$x = 43; // => 42',
        '```',
        '',
    ]));

    $updates = [
        new AssertionUpdate(
            markdownLine: 4,
            type: 'result_comment',
            assertionType: 'result_comment',
            oldValue: '42',
            newValue: '43',
        ),
    ];

    $count = $this->rewriter->rewrite($file, $updates);

    expect($count)->toBe(1);
    expect(file_get_contents($file))->toContain('$x = 43; // => 43');
    expect(file_get_contents($file))->not->toContain('// => 42');
});
test('updates multiple assertions in same file bottom up', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '# Test',
        '',
        '```php',
        'echo "hello";',
        '```',
        '<!-- doctest: wrong1 -->',
        '',
        '```php',
        'echo "world";',
        '```',
        '<!-- doctest: wrong2 -->',
        '',
    ]));

    $updates = [
        new AssertionUpdate(
            markdownLine: 6,
            type: 'html_comment',
            assertionType: 'output',
            oldValue: 'wrong1',
            newValue: 'hello',
        ),
        new AssertionUpdate(
            markdownLine: 11,
            type: 'html_comment',
            assertionType: 'output',
            oldValue: 'wrong2',
            newValue: 'world',
        ),
    ];

    $count = $this->rewriter->rewrite($file, $updates);

    expect($count)->toBe(2);
    $content = file_get_contents($file);
    expect($content)->toContain('<!-- doctest: hello -->');
    expect($content)->toContain('<!-- doctest: world -->');
    expect($content)->not->toContain('wrong1');
    expect($content)->not->toContain('wrong2');
});
test('updates json assertion', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '# Test',
        '',
        '```php',
        'echo json_encode(["a" => 1]);',
        '```',
        '<!-- doctest-json: {"old":true} -->',
        '',
    ]));

    $updates = [
        new AssertionUpdate(
            markdownLine: 6,
            type: 'html_comment',
            assertionType: 'output_json',
            oldValue: '{"old":true}',
            newValue: '{"a":1}',
        ),
    ];

    $count = $this->rewriter->rewrite($file, $updates);

    expect($count)->toBe(1);
    expect(file_get_contents($file))->toContain('<!-- doctest-json: {"a":1} -->');
});
test('returns zero when no updates needed', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, "# Test\n\n```php\necho \"ok\";\n```\n<!-- doctest: ok -->\n");

    $count = $this->rewriter->rewrite($file, []);

    expect($count)->toBe(0);
    expect(file_get_contents($file))->toContain('<!-- doctest: ok -->');
});
test('preserves file content around updates', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '# Title',
        '',
        'Some text before.',
        '',
        '```php',
        'echo "hello";',
        '```',
        '<!-- doctest: wrong -->',
        '',
        'Some text after.',
        '',
    ]));

    $updates = [
        new AssertionUpdate(
            markdownLine: 8,
            type: 'html_comment',
            assertionType: 'output',
            oldValue: 'wrong',
            newValue: 'hello',
        ),
    ];

    $this->rewriter->rewrite($file, $updates);

    $content = file_get_contents($file);
    expect($content)->toContain('# Title');
    expect($content)->toContain('Some text before.');
    expect($content)->toContain('Some text after.');
    expect($content)->toContain('<!-- doctest: hello -->');
});
test('single line value to multi line value', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '```php',
        'echo "a\nb";',
        '```',
        '<!-- doctest: old -->',
        '',
    ]));

    $updates = [
        new AssertionUpdate(
            markdownLine: 4,
            type: 'html_comment',
            assertionType: 'output',
            oldValue: 'old',
            newValue: "a\nb",
        ),
    ];

    $this->rewriter->rewrite($file, $updates);

    $content = file_get_contents($file);
    expect($content)->toContain("<!-- doctest:\na\nb\n-->");
});
test('skips malformed multi line comment without closing delimiter', function (): void {
    $file     = $this->tempDir.'/test.md';
    $original = implode("\n", [
        '```php',
        'echo "hello";',
        '```',
        '<!-- doctest:',
        'broken content without closing',
        '',
    ]);
    file_put_contents($file, $original);

    $updates = [
        new AssertionUpdate(
            markdownLine: 4,
            type: 'html_comment',
            assertionType: 'output',
            oldValue: 'broken content without closing',
            newValue: 'hello',
        ),
    ];

    $count = $this->rewriter->rewrite($file, $updates);

    // Should skip the malformed comment — not corrupt the file
    expect($count)->toBe(0);
    expect(file_get_contents($file))->toBe($original);
});
test('multi line value to single line value', function (): void {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, implode("\n", [
        '```php',
        'echo "hello";',
        '```',
        '<!-- doctest:',
        'old1',
        'old2',
        '-->',
        '',
    ]));

    $updates = [
        new AssertionUpdate(
            markdownLine: 4,
            type: 'html_comment',
            assertionType: 'output',
            oldValue: "old1\nold2",
            newValue: 'hello',
        ),
    ];

    $this->rewriter->rewrite($file, $updates);

    $content = file_get_contents($file);
    expect($content)->toContain('<!-- doctest: hello -->');
    expect($content)->not->toContain('old1');
    expect($content)->not->toContain('old2');
});
