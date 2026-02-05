<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Executor;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\CodeBlock\Attribute;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\Executor\Executor;

final class ExecutorTest extends TestCase
{
    private Executor $executor;

    private AssertionParser $assertionParser;

    protected function setUp(): void
    {
        $this->executor = new Executor();
        $this->assertionParser = new AssertionParser();
    }

    private function makeBlock(string $code, ?Attribute $attribute = null, ?string $throwsClass = null, ?string $throwsMessage = null): CodeBlock
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
            assertions: $parsed->assertions,
        );
    }

    #[Test]
    public function executes_simple_echo_and_captures_output(): void
    {
        $block = $this->makeBlock("echo \"Hello World\";\n// Output: Hello World");
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
        $this->assertSame('Hello World', $result->actualOutput);
    }

    #[Test]
    public function executes_code_with_no_assertions_as_smoke_test(): void
    {
        $block = $this->makeBlock('$x = 42;');
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function returns_skipped_result_for_ignore_attribute(): void
    {
        $block = $this->makeBlock('echo "ignored";', Attribute::Ignore);
        $result = $this->executor->execute($block);

        $this->assertTrue($result->skipped);
        $this->assertTrue($result->passed);
    }

    #[Test]
    public function syntax_checks_no_run_blocks(): void
    {
        $block = $this->makeBlock('$x = 1 + 2;', Attribute::NoRun);
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function detects_parse_errors_for_parse_error_blocks(): void
    {
        $block = $this->makeBlock('$x = {invalid syntax;', Attribute::ParseError);
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
        $block = $this->makeBlock("\$x = 42;\n// Expect: \$x === 42");
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function expect_with_falsy_result_fails(): void
    {
        $block = $this->makeBlock("\$x = 42;\n// Expect: \$x === 99");
        $result = $this->executor->execute($block);

        $this->assertFalse($result->passed);
    }

    #[Test]
    public function multiple_output_assertions_per_block(): void
    {
        $block = $this->makeBlock("echo \"a\";\n// Output: a\necho \"b\";\n// Output: b");
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function handles_process_timeout_gracefully(): void
    {
        $executor = new Executor(timeout: 1);
        $block = $this->makeBlock('sleep(10); echo "done";');
        $result = $executor->execute($block);

        $this->assertFalse($result->passed);
        $this->assertNotNull($result->error);
    }

    #[Test]
    public function output_assertion_failure_reports_diff(): void
    {
        $block = $this->makeBlock("echo \"wrong\";\n// Output: right");
        $result = $this->executor->execute($block);

        $this->assertFalse($result->passed);
        $this->assertSame('wrong', $result->actualOutput);
        $this->assertSame('right', $result->expectedOutput);
    }
}
