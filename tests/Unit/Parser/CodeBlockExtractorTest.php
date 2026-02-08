<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\CodeBlock\Attribute;
use TestFlowLabs\DocTest\Parser\MarkdownParser;
use TestFlowLabs\DocTest\Assertion\OutputAssertion;
use TestFlowLabs\DocTest\Parser\CodeBlockExtractor;

beforeEach(function (): void {
    $this->extractor      = new CodeBlockExtractor();
    $this->markdownParser = new MarkdownParser();
});
test('extracts php blocks from markdown', function (): void {
    $markdown = file_get_contents(__DIR__.'/../../Fixtures/basic.md');
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'basic.md');

    expect($blocks)->toHaveCount(4);
});
test('skips non php blocks', function (): void {
    $markdown = file_get_contents(__DIR__.'/../../Fixtures/mixed-languages.md');
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'mixed-languages.md');

    expect($blocks)->toHaveCount(2);
    $this->assertStringContainsString('PHP works', $blocks[0]->rawCode);
    $this->assertStringContainsString('second PHP', $blocks[1]->rawCode);
});
test('skips blocks without language identifier', function (): void {
    $markdown = "# Test\n\n```\nplain text\n```\n";
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'test.md');

    expect($blocks)->toHaveCount(0);
});
test('handles case insensitive php', function (): void {
    $markdown = "```PHP\necho \"upper\";\n```\n";
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'test.md');

    expect($blocks)->toHaveCount(1);
});
test('strips opening php tag from code', function (): void {
    $markdown = "```php\n<?php\necho \"hello\";\n```\n";
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'test.md');

    expect($blocks)->toHaveCount(1);
    $this->assertStringNotContainsString('<?php', $blocks[0]->executableCode);
    $this->assertStringContainsString('echo "hello"', $blocks[0]->executableCode);
});
test('preserves line numbers from source', function (): void {
    $markdown = file_get_contents(__DIR__.'/../../Fixtures/basic.md');
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'basic.md');

    // First code block starts at line 5 in basic.md
    expect($blocks[0]->startLine)->toBe(5);
});
test('passes parsed attributes to code block', function (): void {
    $markdown = file_get_contents(__DIR__.'/../../Fixtures/attributes.md');
    $document = $this->markdownParser->parse($markdown);

    $blocks      = $this->extractor->extract($document, 'attributes.md');
    $ignoreBlock = array_find($blocks, fn ($block) => $block->attributes->isIgnore());

    expect($ignoreBlock)->not->toBeNull();
    expect($ignoreBlock->attributes->attribute)->toBe(Attribute::Ignore);
});
test('passes html comment assertions to code block', function (): void {
    $markdown = "```php\necho \"test\";\n```\n<!-- doctest: test -->\n";
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'test.md');

    expect($blocks)->toHaveCount(1);
    expect($blocks[0]->assertions)->toHaveCount(1);
    expect($blocks[0]->assertions[0])->toBeInstanceOf(OutputAssertion::class);
});
test('inline output comment is not parsed as assertion', function (): void {
    $markdown = "```php\necho \"test\";\n// Output: test\n```\n";
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'test.md');

    expect($blocks)->toHaveCount(1);
    expect($blocks[0]->assertions)->toHaveCount(0);
    $this->assertStringContainsString('// Output: test', $blocks[0]->executableCode);
});
test('delegates shiki filtering before parsing', function (): void {
    $markdown = file_get_contents(__DIR__.'/../../Fixtures/shiki.md');
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'shiki.md');

    // The shiki block with [!code --] should have line removed
    $diffBlock = $blocks[1];
    // Second block has diff markers
    $this->assertStringNotContainsString('[!code --]', $diffBlock->executableCode);
    $this->assertStringNotContainsString('[!code ++]', $diffBlock->executableCode);
});
test('returns empty array for no php blocks', function (): void {
    $markdown = file_get_contents(__DIR__.'/../../Fixtures/no-php.md');
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'no-php.md');

    expect($blocks)->toHaveCount(0);
});
test('sets file path on code blocks', function (): void {
    $markdown = "```php\necho 1;\n```\n";
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'docs/example.md');

    expect($blocks[0]->file)->toBe('docs/example.md');
});
test('parses html comment output assertion', function (): void {
    $markdown = "```php\necho 'hello';\n```\n<!-- doctest: hello -->\n";
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'test.md');

    expect($blocks)->toHaveCount(1);
    expect($blocks[0]->assertions)->toHaveCount(1);
    expect($blocks[0]->assertions[0])->toBeInstanceOf(OutputAssertion::class);
    expect($blocks[0]->assertions[0]->expected)->toBe('hello');
});
test('html comment assertion keeps code clean', function (): void {
    $markdown = "```php\necho 'hello';\n```\n<!-- doctest: hello -->\n";
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'test.md');

    $this->assertStringNotContainsString('doctest', $blocks[0]->executableCode);
    $this->assertStringNotContainsString('Output', $blocks[0]->executableCode);
    expect($blocks[0]->executableCode)->toBe("echo 'hello';\n");
});
test('parses multi line html comment output assertion', function (): void {
    $markdown = "```php\necho \"Hello\\nWorld\";\n```\n<!-- doctest:\nHello\nWorld\n-->\n";
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'test.md');

    expect($blocks)->toHaveCount(1);
    expect($blocks[0]->assertions)->toHaveCount(1);
    expect($blocks[0]->assertions[0])->toBeInstanceOf(OutputAssertion::class);
    expect($blocks[0]->assertions[0]->expected)->toBe("Hello\nWorld");
});
test('ignores html comments not following php blocks', function (): void {
    $markdown = "<!-- doctest: orphan -->\n\n```php\necho 1;\n```\n";
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'test.md');

    expect($blocks)->toHaveCount(1);
    expect($blocks[0]->assertions)->toHaveCount(0);
});
test('collects multiple consecutive html comment assertions', function (): void {
    $markdown = "```php\necho \"hello world\";\n```\n<!-- doctest: hello world -->\n<!-- doctest-contains: hello -->\n";
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'test.md');

    expect($blocks)->toHaveCount(1);
    expect($blocks[0]->assertions)->toHaveCount(2);
});
test('extracts php block with shiki highlight info string', function (): void {
    $markdown = "```php{1,3-5}\necho \"hi\";\n```\n";
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'test.md');

    expect($blocks)->toHaveCount(1);
});
test('returns empty for empty markdown', function (): void {
    $document = $this->markdownParser->parse('');

    $blocks = $this->extractor->extract($document, 'test.md');

    expect($blocks)->toHaveCount(0);
});
test('strips php tag without trailing newline', function (): void {
    $markdown = "```php\n<?php echo \"hello\";\n```\n";
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'test.md');

    expect($blocks)->toHaveCount(1);
    $this->assertStringNotContainsString('<?php', $blocks[0]->executableCode);
    $this->assertStringContainsString('echo "hello"', $blocks[0]->executableCode);
});
test('preserves raw code with php tag', function (): void {
    $markdown = "```php\n<?php\necho \"hello\";\n```\n";
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'test.md');

    $this->assertStringContainsString('<?php', $blocks[0]->rawCode);
});

test('parses doctest-attr HTML comment before code block as attributes', function (): void {
    $markdown = "<!-- doctest-attr: ignore -->\n```php\necho 'test';\n```\n";
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'test.md');

    expect($blocks)->toHaveCount(1);
    expect($blocks[0]->attributes->attribute)->toBe(Attribute::Ignore);
});

test('parses doctest-attr with group before code block', function (): void {
    $markdown = "<!-- doctest-attr: group=\"cart\" -->\n```php\necho 1;\n```\n";
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'test.md');

    expect($blocks)->toHaveCount(1);
    expect($blocks[0]->attributes->group)->toBe('cart');
});

test('parses doctest-attr with bootstrap before code block', function (): void {
    $markdown = "<!-- doctest-attr: bootstrap=\"laravel\" -->\n```php\necho 1;\n```\n";
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'test.md');

    expect($blocks)->toHaveCount(1);
    expect($blocks[0]->attributes->bootstraps)->toBe(['laravel']);
});

test('doctest-attr comment does not become an assertion', function (): void {
    $markdown = "<!-- doctest-attr: ignore -->\n```php\necho 'test';\n```\n";
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'test.md');

    expect($blocks[0]->assertions)->toHaveCount(0);
});

test('doctest-attr and doctest assertion work together', function (): void {
    $markdown = "<!-- doctest-attr: group=\"math\" -->\n```php\necho 42;\n```\n<!-- doctest: 42 -->\n";
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'test.md');

    expect($blocks)->toHaveCount(1);
    expect($blocks[0]->attributes->group)->toBe('math');
    expect($blocks[0]->assertions)->toHaveCount(1);
    expect($blocks[0]->assertions[0])->toBeInstanceOf(OutputAssertion::class);
});

test('non-doctest-attr HTML comment before block is not parsed as attribute', function (): void {
    $markdown = "<!-- just a regular comment -->\n```php\necho 1;\n```\n";
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'test.md');

    expect($blocks)->toHaveCount(1);
    expect($blocks[0]->attributes->attribute)->toBeNull();
});

test('HTML comment attribute fills in what info string does not set', function (): void {
    $markdown = "<!-- doctest-attr: group=\"cart\" -->\n```php setup\necho 1;\n```\n";
    $document = $this->markdownParser->parse($markdown);

    $blocks = $this->extractor->extract($document, 'test.md');

    expect($blocks)->toHaveCount(1);
    expect($blocks[0]->attributes->attribute)->toBe(Attribute::Setup);
    expect($blocks[0]->attributes->group)->toBe('cart');
});

test('throws RuntimeException when both sources set same attribute', function (): void {
    $markdown = "<!-- doctest-attr: group=\"from-comment\" -->\n```php group=\"from-info\"\necho 1;\n```\n";
    $document = $this->markdownParser->parse($markdown);

    expect(fn () => $this->extractor->extract($document, 'test.md'))
        ->toThrow(RuntimeException::class, 'Conflicting');
});

test('throws RuntimeException when both sources set attribute keyword', function (): void {
    $markdown = "<!-- doctest-attr: no_run -->\n```php ignore\necho 1;\n```\n";
    $document = $this->markdownParser->parse($markdown);

    expect(fn () => $this->extractor->extract($document, 'test.md'))
        ->toThrow(RuntimeException::class, 'Conflicting');
});

test('throws RuntimeException when both sources set bootstrap', function (): void {
    $markdown = "<!-- doctest-attr: bootstrap=\"db\" -->\n```php bootstrap=\"laravel\"\necho 1;\n```\n";
    $document = $this->markdownParser->parse($markdown);

    expect(fn () => $this->extractor->extract($document, 'test.md'))
        ->toThrow(RuntimeException::class, 'Conflicting');
});
test('throws RuntimeException when multiple doctest-attr comments before code block', function (): void {
    $markdown = "<!-- doctest-attr: group=\"a\" -->\n<!-- doctest-attr: bootstrap=\"db\" -->\n```php\necho 1;\n```\n";
    $document = $this->markdownParser->parse($markdown);

    expect(fn () => $this->extractor->extract($document, 'test.md'))
        ->toThrow(RuntimeException::class, 'Multiple');
});
test('throws RuntimeException when both sources set throwsClass', function (): void {
    $markdown = "<!-- doctest-attr: throws(InvalidArgumentException) -->\n```php throws(RuntimeException)\nthrow new \\Exception('x');\n```\n";
    $document = $this->markdownParser->parse($markdown);

    expect(fn () => $this->extractor->extract($document, 'test.md'))
        ->toThrow(RuntimeException::class, 'Conflicting');
});
