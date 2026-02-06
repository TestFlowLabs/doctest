<?php

declare(strict_types=1);
use League\CommonMark\Node\Block\Document;
use TestFlowLabs\DocTest\Parser\MarkdownParser;

beforeEach(function (): void {
    $this->parser = new MarkdownParser();
});
test('parses markdown into document', function (): void {
    $document = $this->parser->parse("# Hello\n\n```php\necho 'hi';\n```\n");

    expect($document)->toBeInstanceOf(Document::class);
});
test('returns document type', function (): void {
    $document = $this->parser->parse('# Just a heading');

    expect($document)->toBeInstanceOf(Document::class);
});
test('handles empty string', function (): void {
    $document = $this->parser->parse('');

    expect($document)->toBeInstanceOf(Document::class);
});
test('handles markdown with no code blocks', function (): void {
    $document = $this->parser->parse("# Title\n\nSome paragraph text.\n\n- Item 1\n- Item 2\n");

    expect($document)->toBeInstanceOf(Document::class);
});
