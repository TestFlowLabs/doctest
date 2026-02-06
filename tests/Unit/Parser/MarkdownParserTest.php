<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Parser;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use League\CommonMark\Node\Block\Document;
use TestFlowLabs\DocTest\Parser\MarkdownParser;

final class MarkdownParserTest extends TestCase
{
    private MarkdownParser $parser;

    protected function setUp(): void
    {
        $this->parser = new MarkdownParser();
    }

    #[Test]
    public function parses_markdown_into_document(): void
    {
        $document = $this->parser->parse("# Hello\n\n```php\necho 'hi';\n```\n");

        $this->assertInstanceOf(Document::class, $document);
    }

    #[Test]
    public function returns_document_type(): void
    {
        $document = $this->parser->parse('# Just a heading');

        $this->assertInstanceOf(Document::class, $document);
    }

    #[Test]
    public function handles_empty_string(): void
    {
        $document = $this->parser->parse('');

        $this->assertInstanceOf(Document::class, $document);
    }

    #[Test]
    public function handles_markdown_with_no_code_blocks(): void
    {
        $document = $this->parser->parse("# Title\n\nSome paragraph text.\n\n- Item 1\n- Item 2\n");

        $this->assertInstanceOf(Document::class, $document);
    }
}
