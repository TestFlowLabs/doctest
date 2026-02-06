<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Reporter;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Reporter\ErrorFormatter;
use TestFlowLabs\DocTest\Executor\ExecutionResult;

final class ErrorFormatterTest extends TestCase
{
    private ErrorFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new ErrorFormatter();
    }

    private function makeResult(string $code, ?string $error = null, ?string $actualOutput = null, ?string $expectedOutput = null, ?string $diff = null): ExecutionResult
    {
        $block = new CodeBlock(
            file: 'docs/example.md',
            startLine: 10,
            rawCode: $code,
            executableCode: $code,
            attributes: new Attributes(),
            assertions: [],
        );

        return new ExecutionResult(
            passed: false,
            codeBlock: $block,
            actualOutput: $actualOutput,
            expectedOutput: $expectedOutput,
            diff: $diff,
            error: $error,
        );
    }

    #[Test]
    public function formats_with_file_line_location_header(): void
    {
        $result = $this->makeResult('echo "test";', 'Some error');
        $output = $this->formatter->format($result);

        $this->assertStringContainsString('docs/example.md:10', $output);
    }

    #[Test]
    public function shows_code_context_with_line_numbers(): void
    {
        $result = $this->makeResult("echo \"hello\";\necho \"world\";", 'Error occurred');
        $output = $this->formatter->format($result);

        $this->assertStringContainsString('10 |', $output);
        $this->assertStringContainsString('echo "hello"', $output);
    }

    #[Test]
    public function shows_error_message(): void
    {
        $result = $this->makeResult('echo "test";', 'Undefined variable $x');
        $output = $this->formatter->format($result);

        $this->assertStringContainsString('Undefined variable $x', $output);
    }

    #[Test]
    public function formats_assertion_failure_with_expected_vs_actual(): void
    {
        $result = $this->makeResult(
            'echo "actual";',
            null,
            'actual',
            'expected',
            "- expected\n+ actual",
        );
        $output = $this->formatter->format($result);

        $this->assertStringContainsString('Expected', $output);
        $this->assertStringContainsString('Actual', $output);
    }

    #[Test]
    public function formats_timeout_error(): void
    {
        $result = $this->makeResult('sleep(100);', 'Process timed out after 5 seconds');
        $output = $this->formatter->format($result);

        $this->assertStringContainsString('timed out', $output);
    }

    #[Test]
    public function formats_diff_output(): void
    {
        $result = $this->makeResult(
            'echo "wrong";',
            null,
            'wrong',
            'right',
            "- right\n+ wrong",
        );
        $output = $this->formatter->format($result);

        $this->assertStringContainsString('- right', $output);
        $this->assertStringContainsString('+ wrong', $output);
    }

    #[Test]
    public function shows_context_window_around_error(): void
    {
        $code   = "line1;\nline2;\nline3;\nline4;\nline5;";
        $result = $this->makeResult($code, 'Error on line3');
        $output = $this->formatter->format($result);

        $this->assertStringContainsString('line1', $output);
        $this->assertStringContainsString('line5', $output);
    }
}
