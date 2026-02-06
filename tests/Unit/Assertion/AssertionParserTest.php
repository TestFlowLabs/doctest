<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Assertion;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\Assertion\ResultCommentAssertion;

final class AssertionParserTest extends TestCase
{
    private AssertionParser $parser;

    protected function setUp(): void
    {
        $this->parser = new AssertionParser();
    }

    #[Test]
    public function preserves_non_assertion_comments(): void
    {
        $result = $this->parser->parse("// This is a regular comment\n\$x = 1;");

        $this->assertStringContainsString('// This is a regular comment', $result->executableCode);
    }

    #[Test]
    public function handles_block_with_no_assertions(): void
    {
        $result = $this->parser->parse('$x = 42;');

        $this->assertEmpty($result->resultComments);
        $this->assertSame('$x = 42;', $result->executableCode);
    }

    #[Test]
    public function output_comment_is_treated_as_regular_comment(): void
    {
        $result = $this->parser->parse("echo \"Hello\";\n// Output: Hello");

        $this->assertEmpty($result->resultComments);
        $this->assertStringContainsString('// Output: Hello', $result->executableCode);
    }

    #[Test]
    public function expect_comment_is_treated_as_regular_comment(): void
    {
        $result = $this->parser->parse("\$sum = 1 + 2;\n// Expect: \$sum === 3");

        $this->assertEmpty($result->resultComments);
        $this->assertStringContainsString('// Expect: $sum === 3', $result->executableCode);
    }

    #[Test]
    public function output_contains_comment_is_treated_as_regular_comment(): void
    {
        $result = $this->parser->parse("echo \"Hello World\";\n// OutputContains: World");

        $this->assertEmpty($result->resultComments);
        $this->assertStringContainsString('// OutputContains: World', $result->executableCode);
    }

    #[Test]
    public function output_matches_comment_is_treated_as_regular_comment(): void
    {
        $result = $this->parser->parse("echo \"Order #1234\";\n// OutputMatches: /Order #\\d{4}/");

        $this->assertEmpty($result->resultComments);
        $this->assertStringContainsString('// OutputMatches:', $result->executableCode);
    }

    #[Test]
    public function output_json_comment_is_treated_as_regular_comment(): void
    {
        $result = $this->parser->parse("echo json_encode(['key' => 'val']);\n// OutputJson: {\"key\": \"val\"}");

        $this->assertEmpty($result->resultComments);
        $this->assertStringContainsString('// OutputJson:', $result->executableCode);
    }

    #[Test]
    public function finds_simple_result_comment(): void
    {
        $result = $this->parser->parse('$x = 42; // => 42');

        $this->assertCount(1, $result->resultComments);
        $this->assertInstanceOf(ResultCommentAssertion::class, $result->resultComments[0]);
        $this->assertSame('$x = 42', $result->resultComments[0]->expression);
        $this->assertSame('42', $result->resultComments[0]->expectedValue);
    }

    #[Test]
    public function finds_boolean_result_comment(): void
    {
        $result = $this->parser->parse('$state->matches(\'green\'); // => true');

        $this->assertCount(1, $result->resultComments);
        $this->assertSame('$state->matches(\'green\')', $result->resultComments[0]->expression);
        $this->assertSame('true', $result->resultComments[0]->expectedValue);
    }

    #[Test]
    public function finds_string_result_comment(): void
    {
        $result = $this->parser->parse('$name = \'Alice\'; // => \'Alice\'');

        $this->assertCount(1, $result->resultComments);
        $this->assertSame('$name = \'Alice\'', $result->resultComments[0]->expression);
        $this->assertSame('\'Alice\'', $result->resultComments[0]->expectedValue);
    }

    #[Test]
    public function finds_null_result_comment(): void
    {
        $result = $this->parser->parse('$result = null; // => NULL');

        $this->assertCount(1, $result->resultComments);
        $this->assertSame('$result = null', $result->resultComments[0]->expression);
        $this->assertSame('NULL', $result->resultComments[0]->expectedValue);
    }

    #[Test]
    public function strips_semicolon_from_result_comment_expression(): void
    {
        $result = $this->parser->parse('$x = 42; // => 42');

        $this->assertSame('$x = 42', $result->resultComments[0]->expression);
    }

    #[Test]
    public function result_comment_preserves_line_number(): void
    {
        $result = $this->parser->parse("\$a = 1;\n\$b = 2; // => 2");

        $this->assertCount(1, $result->resultComments);
        $this->assertSame(2, $result->resultComments[0]->line());
    }

    #[Test]
    public function result_comment_keeps_expression_in_executable_code(): void
    {
        $result = $this->parser->parse('$x = 42; // => 42');

        $this->assertStringContainsString('$x = 42;', $result->executableCode);
        $this->assertStringNotContainsString('// =>', $result->executableCode);
    }

    #[Test]
    public function handles_multiple_result_comments(): void
    {
        $code   = "\$x = 1; // => 1\n\$y = 2; // => 2\n\$z = 3; // => 3";
        $result = $this->parser->parse($code);

        $this->assertCount(3, $result->resultComments);
        $this->assertSame('1', $result->resultComments[0]->expectedValue);
        $this->assertSame('2', $result->resultComments[1]->expectedValue);
        $this->assertSame('3', $result->resultComments[2]->expectedValue);
    }

    #[Test]
    public function result_comment_does_not_match_regular_comments(): void
    {
        $result = $this->parser->parse("// This is a regular comment\n\$x = 1;");

        $this->assertEmpty($result->resultComments);
    }

    #[Test]
    public function result_comment_does_not_match_arrow_in_array(): void
    {
        $result = $this->parser->parse("\$arr = ['key' => 'value'];");

        $this->assertEmpty($result->resultComments);
    }

    #[Test]
    public function result_comment_handles_spacing_variations(): void
    {
        $result1 = $this->parser->parse('$x = 1; //=> 1');
        $result2 = $this->parser->parse('$x = 1; // =>1');
        $result3 = $this->parser->parse('$x = 1; //=>1');

        $this->assertCount(1, $result1->resultComments);
        $this->assertCount(1, $result2->resultComments);
        $this->assertCount(1, $result3->resultComments);
        $this->assertSame('1', $result1->resultComments[0]->expectedValue);
        $this->assertSame('1', $result2->resultComments[0]->expectedValue);
        $this->assertSame('1', $result3->resultComments[0]->expectedValue);
    }

    #[Test]
    public function result_comment_without_semicolon(): void
    {
        $result = $this->parser->parse('is_string($x) // => false');

        $this->assertCount(1, $result->resultComments);
        $this->assertSame('is_string($x)', $result->resultComments[0]->expression);
        $this->assertSame('false', $result->resultComments[0]->expectedValue);
    }
}
