<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Assertion;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\Assertion\ExpectAssertion;
use TestFlowLabs\DocTest\Assertion\OutputAssertion;

final class AssertionParserTest extends TestCase
{
    private AssertionParser $parser;

    protected function setUp(): void
    {
        $this->parser = new AssertionParser();
    }

    #[Test]
    public function finds_single_line_output(): void
    {
        $result = $this->parser->parse("echo \"Hello\";\n// Output: Hello");

        $this->assertCount(1, $result->assertions);
        $this->assertInstanceOf(OutputAssertion::class, $result->assertions[0]);
        $this->assertSame('Hello', $result->assertions[0]->expected);
    }

    #[Test]
    public function finds_multiline_output(): void
    {
        $code = "print_r([1, 2]);\n// Output:\n// Array\n// (\n//     [0] => 1\n//     [1] => 2\n// )";
        $result = $this->parser->parse($code);

        $this->assertCount(1, $result->assertions);
        $this->assertInstanceOf(OutputAssertion::class, $result->assertions[0]);
        $expected = "Array\n(\n    [0] => 1\n    [1] => 2\n)";
        $this->assertSame($expected, $result->assertions[0]->expected);
    }

    #[Test]
    public function finds_expect_assertion(): void
    {
        $result = $this->parser->parse("\$sum = 1 + 2;\n// Expect: \$sum === 3");

        $this->assertCount(1, $result->expects);
        $this->assertInstanceOf(ExpectAssertion::class, $result->expects[0]);
        $this->assertSame('$sum === 3', $result->expects[0]->expression);
    }

    #[Test]
    public function strips_assertion_comments_from_executable_code(): void
    {
        $result = $this->parser->parse("echo \"Hi\";\n// Output: Hi");

        $this->assertSame("echo \"Hi\";", trim($result->executableCode));
    }

    #[Test]
    public function preserves_non_assertion_comments(): void
    {
        $result = $this->parser->parse("// This is a regular comment\n\$x = 1;");

        $this->assertStringContainsString('// This is a regular comment', $result->executableCode);
    }

    #[Test]
    public function handles_multiple_assertions(): void
    {
        $code = "echo \"a\";\n// Output: a\necho \"b\";\n// Output: b";
        $result = $this->parser->parse($code);

        $this->assertCount(2, $result->assertions);
    }

    #[Test]
    public function handles_block_with_no_assertions(): void
    {
        $result = $this->parser->parse("\$x = 42;");

        $this->assertEmpty($result->assertions);
        $this->assertEmpty($result->expects);
        $this->assertSame('$x = 42;', $result->executableCode);
    }

    #[Test]
    public function handles_mixed_output_and_expect(): void
    {
        $code = "echo \"Hi\";\n// Output: Hi\n\$x = 1;\n// Expect: \$x === 1";
        $result = $this->parser->parse($code);

        $this->assertCount(1, $result->assertions);
        $this->assertCount(1, $result->expects);
    }

    #[Test]
    public function segments_code_at_output_boundaries(): void
    {
        $code = "echo \"a\";\n// Output: a\necho \"b\";\n// Output: b";
        $result = $this->parser->parse($code);

        $this->assertCount(2, $result->segments);
        $this->assertSame('echo "a";', trim($result->segments[0]->code));
        $this->assertSame('echo "b";', trim($result->segments[1]->code));
        $this->assertNotNull($result->segments[0]->outputAssertion);
        $this->assertSame('a', $result->segments[0]->outputAssertion->expected);
    }

    #[Test]
    public function multiline_continuation_strips_prefix(): void
    {
        $code = "echo \"line1\\nline2\";\n// Output:\n// line1\n// line2";
        $result = $this->parser->parse($code);

        $this->assertSame("line1\nline2", $result->assertions[0]->expected);
    }

    #[Test]
    public function preserves_internal_indentation_in_multiline(): void
    {
        $code = "echo \"  indented\";\n// Output:\n//   indented";
        $result = $this->parser->parse($code);

        $this->assertSame('  indented', $result->assertions[0]->expected);
    }
}
