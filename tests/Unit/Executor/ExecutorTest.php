<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Executor;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\Executor\Executor;
use TestFlowLabs\DocTest\CodeBlock\Attribute;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\Assertion\ExpectAssertion;
use TestFlowLabs\DocTest\Assertion\OutputAssertion;

final class ExecutorTest extends TestCase
{
    private Executor $executor;
    private AssertionParser $assertionParser;

    protected function setUp(): void
    {
        $this->executor        = new Executor();
        $this->assertionParser = new AssertionParser();
    }

    /**
     * @param  array<\TestFlowLabs\DocTest\Assertion\Assertion>  $assertions
     */
    private function makeBlock(string $code, ?Attribute $attribute = null, ?string $throwsClass = null, ?string $throwsMessage = null, array $assertions = []): CodeBlock
    {
        $parsed = $this->assertionParser->parse($code);

        return new CodeBlock(
            file: 'test.md',
            startLine: 1,
            rawCode: $code,
            executableCode: $parsed->executableCode,
            attributes: new Attributes(
                attribute: $attribute,
                throwsClass: $throwsClass,
                throwsMessage: $throwsMessage,
            ),
            assertions: $assertions,
        );
    }

    #[Test]
    public function executes_simple_echo_and_captures_output(): void
    {
        $block = $this->makeBlock(
            'echo "Hello World";',
            assertions: [new OutputAssertion('Hello World', 1)],
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
        $this->assertSame('Hello World', $result->actualOutput);
    }

    #[Test]
    public function executes_code_with_no_assertions_as_smoke_test(): void
    {
        $block  = $this->makeBlock('$x = 42;');
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function returns_skipped_result_for_ignore_attribute(): void
    {
        $block  = $this->makeBlock('echo "ignored";', Attribute::Ignore);
        $result = $this->executor->execute($block);

        $this->assertTrue($result->skipped);
        $this->assertTrue($result->passed);
    }

    #[Test]
    public function syntax_checks_no_run_blocks(): void
    {
        $block  = $this->makeBlock('$x = 1 + 2;', Attribute::NoRun);
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function detects_parse_errors_for_parse_error_blocks(): void
    {
        $block  = $this->makeBlock('$x = {invalid syntax;', Attribute::ParseError);
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function captures_exceptions_via_throws_attribute(): void
    {
        $block = $this->makeBlock(
            'throw new \RuntimeException("test error");',
            Attribute::Throws,
            'RuntimeException',
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function throws_attribute_fails_when_no_exception_thrown(): void
    {
        $block = $this->makeBlock(
            '$x = 1;',
            Attribute::Throws,
            'RuntimeException',
        );
        $result = $this->executor->execute($block);

        $this->assertFalse($result->passed);
    }

    #[Test]
    public function evaluates_expect_expressions(): void
    {
        $block = $this->makeBlock(
            '$x = 42;',
            assertions: [new ExpectAssertion('$x === 42', 2)],
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function expect_with_falsy_result_fails(): void
    {
        $block = $this->makeBlock(
            '$x = 42;',
            assertions: [new ExpectAssertion('$x === 99', 2)],
        );
        $result = $this->executor->execute($block);

        $this->assertFalse($result->passed);
    }

    #[Test]
    public function output_assertion_with_combined_output(): void
    {
        $block = $this->makeBlock(
            "echo \"a\";\necho \"b\";",
            assertions: [new OutputAssertion('ab', 1)],
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function handles_process_timeout_gracefully(): void
    {
        $executor = new Executor(timeout: 1);
        $block    = $this->makeBlock('sleep(10); echo "done";');
        $result   = $executor->execute($block);

        $this->assertFalse($result->passed);
        $this->assertNotNull($result->error);
    }

    #[Test]
    public function output_assertion_failure_reports_diff(): void
    {
        $block = $this->makeBlock(
            'echo "wrong";',
            assertions: [new OutputAssertion('right', 2)],
        );
        $result = $this->executor->execute($block);

        $this->assertFalse($result->passed);
        $this->assertSame('wrong', $result->actualOutput);
        $this->assertSame('right', $result->expectedOutput);
    }

    #[Test]
    public function evaluates_result_comment_with_matching_value(): void
    {
        $block  = $this->makeBlock('$x = 42; // => 42');
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function evaluates_result_comment_with_boolean(): void
    {
        $block  = $this->makeBlock('$x = true; // => true');
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function evaluates_result_comment_with_null(): void
    {
        $block  = $this->makeBlock('$x = null; // => NULL');
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function result_comment_fails_on_mismatch(): void
    {
        $block  = $this->makeBlock('$x = 42; // => 99');
        $result = $this->executor->execute($block);

        $this->assertFalse($result->passed);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('result_comment', $result->error);
    }

    #[Test]
    public function evaluates_multiple_result_comments(): void
    {
        $block  = $this->makeBlock("\$x = 1; // => 1\n\$y = 2; // => 2\n\$z = \$x + \$y; // => 3");
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function result_comment_mixed_with_html_output_assertion(): void
    {
        $block = $this->makeBlock(
            "\$x = 42; // => 42\necho \$x;",
            assertions: [new OutputAssertion('42', 3)],
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function populates_assertion_details_for_output(): void
    {
        $block = $this->makeBlock(
            'echo "Hello";',
            assertions: [new OutputAssertion('Hello', 2)],
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
        $this->assertCount(1, $result->assertionDetails);
        $this->assertSame('output', $result->assertionDetails[0]->type);
        $this->assertTrue($result->assertionDetails[0]->passed);
        $this->assertSame('Hello', $result->assertionDetails[0]->expected);
        $this->assertSame('Hello', $result->assertionDetails[0]->actual);
    }

    #[Test]
    public function populates_assertion_details_for_result_comment(): void
    {
        $block  = $this->makeBlock('$x = 42; // => 42');
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
        $this->assertCount(1, $result->assertionDetails);
        $this->assertSame('result_comment', $result->assertionDetails[0]->type);
        $this->assertTrue($result->assertionDetails[0]->passed);
        $this->assertSame('42', $result->assertionDetails[0]->expected);
        $this->assertSame('$x = 42', $result->assertionDetails[0]->expression);
    }

    #[Test]
    public function populates_assertion_details_for_expect(): void
    {
        $block = $this->makeBlock(
            '$x = 42;',
            assertions: [new ExpectAssertion('$x === 42', 2)],
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
        $this->assertCount(1, $result->assertionDetails);
        $this->assertSame('expect', $result->assertionDetails[0]->type);
        $this->assertTrue($result->assertionDetails[0]->passed);
    }

    #[Test]
    public function populates_assertion_details_on_failure(): void
    {
        $block  = $this->makeBlock("\$x = 1; // => 1\n\$y = 2; // => 99");
        $result = $this->executor->execute($block);

        $this->assertFalse($result->passed);
        $this->assertCount(2, $result->assertionDetails);
        $this->assertTrue($result->assertionDetails[0]->passed);
        $this->assertFalse($result->assertionDetails[1]->passed);
    }

    #[Test]
    public function populates_multiple_assertion_details(): void
    {
        $block = $this->makeBlock(
            "echo \"a\";\necho \"b\";",
            assertions: [
                new OutputAssertion('ab', 3),
            ],
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
        $this->assertCount(1, $result->assertionDetails);
        $this->assertSame('ab', $result->assertionDetails[0]->expected);
    }
}
