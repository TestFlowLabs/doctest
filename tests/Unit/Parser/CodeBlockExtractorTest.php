<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Parser;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\CodeBlock\Attribute;
use TestFlowLabs\DocTest\Parser\MarkdownParser;
use TestFlowLabs\DocTest\Assertion\OutputAssertion;
use TestFlowLabs\DocTest\Parser\CodeBlockExtractor;

final class CodeBlockExtractorTest extends TestCase
{
    private CodeBlockExtractor $extractor;
    private MarkdownParser $markdownParser;

    protected function setUp(): void
    {
        $this->extractor      = new CodeBlockExtractor();
        $this->markdownParser = new MarkdownParser();
    }

    #[Test]
    public function extracts_php_blocks_from_markdown(): void
    {
        $markdown = file_get_contents(__DIR__.'/../../Fixtures/basic.md');
        $document = $this->markdownParser->parse($markdown);

        $blocks = $this->extractor->extract($document, 'basic.md');

        $this->assertCount(4, $blocks);
    }

    #[Test]
    public function skips_non_php_blocks(): void
    {
        $markdown = file_get_contents(__DIR__.'/../../Fixtures/mixed-languages.md');
        $document = $this->markdownParser->parse($markdown);

        $blocks = $this->extractor->extract($document, 'mixed-languages.md');

        $this->assertCount(2, $blocks);
        $this->assertStringContainsString('PHP works', $blocks[0]->rawCode);
        $this->assertStringContainsString('second PHP', $blocks[1]->rawCode);
    }

    #[Test]
    public function skips_blocks_without_language_identifier(): void
    {
        $markdown = "# Test\n\n```\nplain text\n```\n";
        $document = $this->markdownParser->parse($markdown);

        $blocks = $this->extractor->extract($document, 'test.md');

        $this->assertCount(0, $blocks);
    }

    #[Test]
    public function handles_case_insensitive_php(): void
    {
        $markdown = "```PHP\necho \"upper\";\n```\n";
        $document = $this->markdownParser->parse($markdown);

        $blocks = $this->extractor->extract($document, 'test.md');

        $this->assertCount(1, $blocks);
    }

    #[Test]
    public function strips_opening_php_tag_from_code(): void
    {
        $markdown = "```php\n<?php\necho \"hello\";\n```\n";
        $document = $this->markdownParser->parse($markdown);

        $blocks = $this->extractor->extract($document, 'test.md');

        $this->assertCount(1, $blocks);
        $this->assertStringNotContainsString('<?php', $blocks[0]->executableCode);
        $this->assertStringContainsString('echo "hello"', $blocks[0]->executableCode);
    }

    #[Test]
    public function preserves_line_numbers_from_source(): void
    {
        $markdown = file_get_contents(__DIR__.'/../../Fixtures/basic.md');
        $document = $this->markdownParser->parse($markdown);

        $blocks = $this->extractor->extract($document, 'basic.md');

        // First code block starts at line 5 in basic.md
        $this->assertSame(5, $blocks[0]->startLine);
    }

    #[Test]
    public function passes_parsed_attributes_to_code_block(): void
    {
        $markdown = file_get_contents(__DIR__.'/../../Fixtures/attributes.md');
        $document = $this->markdownParser->parse($markdown);

        $blocks      = $this->extractor->extract($document, 'attributes.md');
        $ignoreBlock = array_find($blocks, fn ($block) => $block->attributes->isIgnore());

        $this->assertNotNull($ignoreBlock);
        $this->assertSame(Attribute::Ignore, $ignoreBlock->attributes->attribute);
    }

    #[Test]
    public function passes_html_comment_assertions_to_code_block(): void
    {
        $markdown = "```php\necho \"test\";\n```\n<!-- doctest: test -->\n";
        $document = $this->markdownParser->parse($markdown);

        $blocks = $this->extractor->extract($document, 'test.md');

        $this->assertCount(1, $blocks);
        $this->assertCount(1, $blocks[0]->assertions);
        $this->assertInstanceOf(OutputAssertion::class, $blocks[0]->assertions[0]);
    }

    #[Test]
    public function inline_output_comment_is_not_parsed_as_assertion(): void
    {
        $markdown = "```php\necho \"test\";\n// Output: test\n```\n";
        $document = $this->markdownParser->parse($markdown);

        $blocks = $this->extractor->extract($document, 'test.md');

        $this->assertCount(1, $blocks);
        $this->assertCount(0, $blocks[0]->assertions);
        $this->assertStringContainsString('// Output: test', $blocks[0]->executableCode);
    }

    #[Test]
    public function delegates_shiki_filtering_before_parsing(): void
    {
        $markdown = file_get_contents(__DIR__.'/../../Fixtures/shiki.md');
        $document = $this->markdownParser->parse($markdown);

        $blocks = $this->extractor->extract($document, 'shiki.md');

        // The shiki block with [!code --] should have line removed
        $diffBlock = $blocks[1]; // Second block has diff markers
        $this->assertStringNotContainsString('[!code --]', $diffBlock->executableCode);
        $this->assertStringNotContainsString('[!code ++]', $diffBlock->executableCode);
    }

    #[Test]
    public function returns_empty_array_for_no_php_blocks(): void
    {
        $markdown = file_get_contents(__DIR__.'/../../Fixtures/no-php.md');
        $document = $this->markdownParser->parse($markdown);

        $blocks = $this->extractor->extract($document, 'no-php.md');

        $this->assertCount(0, $blocks);
    }

    #[Test]
    public function sets_file_path_on_code_blocks(): void
    {
        $markdown = "```php\necho 1;\n```\n";
        $document = $this->markdownParser->parse($markdown);

        $blocks = $this->extractor->extract($document, 'docs/example.md');

        $this->assertSame('docs/example.md', $blocks[0]->file);
    }

    #[Test]
    public function parses_html_comment_output_assertion(): void
    {
        $markdown = "```php\necho 'hello';\n```\n<!-- doctest: hello -->\n";
        $document = $this->markdownParser->parse($markdown);

        $blocks = $this->extractor->extract($document, 'test.md');

        $this->assertCount(1, $blocks);
        $this->assertCount(1, $blocks[0]->assertions);
        $this->assertInstanceOf(OutputAssertion::class, $blocks[0]->assertions[0]);
        $this->assertSame('hello', $blocks[0]->assertions[0]->expected);
    }

    #[Test]
    public function html_comment_assertion_keeps_code_clean(): void
    {
        $markdown = "```php\necho 'hello';\n```\n<!-- doctest: hello -->\n";
        $document = $this->markdownParser->parse($markdown);

        $blocks = $this->extractor->extract($document, 'test.md');

        $this->assertStringNotContainsString('doctest', $blocks[0]->executableCode);
        $this->assertStringNotContainsString('Output', $blocks[0]->executableCode);
        $this->assertSame("echo 'hello';\n", $blocks[0]->executableCode);
    }

    #[Test]
    public function parses_multi_line_html_comment_output_assertion(): void
    {
        $markdown = "```php\necho \"Hello\\nWorld\";\n```\n<!-- doctest:\nHello\nWorld\n-->\n";
        $document = $this->markdownParser->parse($markdown);

        $blocks = $this->extractor->extract($document, 'test.md');

        $this->assertCount(1, $blocks);
        $this->assertCount(1, $blocks[0]->assertions);
        $this->assertInstanceOf(OutputAssertion::class, $blocks[0]->assertions[0]);
        $this->assertSame("Hello\nWorld", $blocks[0]->assertions[0]->expected);
    }

    #[Test]
    public function ignores_html_comments_not_following_php_blocks(): void
    {
        $markdown = "<!-- doctest: orphan -->\n\n```php\necho 1;\n```\n";
        $document = $this->markdownParser->parse($markdown);

        $blocks = $this->extractor->extract($document, 'test.md');

        $this->assertCount(1, $blocks);
        $this->assertCount(0, $blocks[0]->assertions);
    }
}
