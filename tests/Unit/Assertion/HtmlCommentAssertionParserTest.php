<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Assertion;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Assertion\ExpectAssertion;
use TestFlowLabs\DocTest\Assertion\HtmlCommentAssertionParser;
use TestFlowLabs\DocTest\Assertion\OutputAssertion;
use TestFlowLabs\DocTest\Assertion\OutputContainsAssertion;
use TestFlowLabs\DocTest\Assertion\OutputJsonAssertion;
use TestFlowLabs\DocTest\Assertion\OutputMatchesAssertion;

final class HtmlCommentAssertionParserTest extends TestCase
{
    private HtmlCommentAssertionParser $parser;

    protected function setUp(): void
    {
        $this->parser = new HtmlCommentAssertionParser();
    }

    #[Test]
    public function parses_output_assertion(): void
    {
        $result = $this->parser->parse('<!-- doctest: Hello, World! -->');

        $this->assertCount(1, $result);
        $this->assertInstanceOf(OutputAssertion::class, $result[0]);
        $this->assertSame('Hello, World!', $result[0]->expected);
    }

    #[Test]
    public function parses_contains_assertion(): void
    {
        $result = $this->parser->parse('<!-- doctest-contains: World -->');

        $this->assertCount(1, $result);
        $this->assertInstanceOf(OutputContainsAssertion::class, $result[0]);
        $this->assertSame('World', $result[0]->expected);
    }

    #[Test]
    public function parses_matches_assertion(): void
    {
        $result = $this->parser->parse('<!-- doctest-matches: /\d+/ -->');

        $this->assertCount(1, $result);
        $this->assertInstanceOf(OutputMatchesAssertion::class, $result[0]);
        $this->assertSame('/\d+/', $result[0]->pattern);
    }

    #[Test]
    public function parses_json_assertion(): void
    {
        $result = $this->parser->parse('<!-- doctest-json: {"key":"value"} -->');

        $this->assertCount(1, $result);
        $this->assertInstanceOf(OutputJsonAssertion::class, $result[0]);
        $this->assertSame('{"key":"value"}', $result[0]->expectedJson);
    }

    #[Test]
    public function parses_expect_assertion(): void
    {
        $result = $this->parser->parse('<!-- doctest-expect: $result === 5 -->');

        $this->assertCount(1, $result);
        $this->assertInstanceOf(ExpectAssertion::class, $result[0]);
        $this->assertSame('$result === 5', $result[0]->expression);
    }

    #[Test]
    public function parses_multi_line_output_assertion(): void
    {
        $html = "<!-- doctest:\nHello\nWorld\n-->";

        $result = $this->parser->parse($html);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(OutputAssertion::class, $result[0]);
        $this->assertSame("Hello\nWorld", $result[0]->expected);
    }

    #[Test]
    public function parses_multi_line_json_assertion(): void
    {
        $html = "<!-- doctest-json:\n{\n  \"key\": \"value\"\n}\n-->";

        $result = $this->parser->parse($html);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(OutputJsonAssertion::class, $result[0]);
        $this->assertSame("{\n  \"key\": \"value\"\n}", $result[0]->expectedJson);
    }

    #[Test]
    public function parses_multi_line_output_with_empty_lines(): void
    {
        $html = "<!-- doctest:\nLine 1\n\nLine 3\n-->";

        $result = $this->parser->parse($html);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(OutputAssertion::class, $result[0]);
        $this->assertSame("Line 1\n\nLine 3", $result[0]->expected);
    }

    #[Test]
    public function returns_empty_for_non_doctest_comment(): void
    {
        $result = $this->parser->parse('<!-- just a regular comment -->');

        $this->assertCount(0, $result);
    }

    #[Test]
    public function returns_empty_for_non_html_input(): void
    {
        $result = $this->parser->parse('not an html comment');

        $this->assertCount(0, $result);
    }
}
