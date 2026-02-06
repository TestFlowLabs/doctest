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

    // --- Edge cases ---

    #[Test]
    public function formats_result_with_no_error_and_no_assertion_failure(): void
    {
        $result = $this->makeResult('$x = 1;');
        $output = $this->formatter->format($result);

        $this->assertStringContainsString('docs/example.md:10', $output);
        $this->assertStringContainsString('$x = 1;', $output);
        $this->assertStringNotContainsString('Error:', $output);
        $this->assertStringNotContainsString('Expected:', $output);
    }

    #[Test]
    public function line_numbers_increment_for_multiline_code(): void
    {
        $code   = "line1;\nline2;\nline3;";
        $result = $this->makeResult($code, 'err');
        $output = $this->formatter->format($result);

        $this->assertStringContainsString('10 | line1;', $output);
        $this->assertStringContainsString('11 | line2;', $output);
        $this->assertStringContainsString('12 | line3;', $output);
    }

    #[Test]
    public function only_expected_without_actual_does_not_show_comparison(): void
    {
        $result = $this->makeResult('echo "test";', null, null, 'expected value');
        $output = $this->formatter->format($result);

        $this->assertStringNotContainsString('Expected:', $output);
        $this->assertStringNotContainsString('Actual:', $output);
    }

    #[Test]
    public function error_and_assertion_failure_both_shown(): void
    {
        $result = $this->makeResult(
            'echo "wrong";',
            'Assertion failed',
            'wrong',
            'right',
        );
        $output = $this->formatter->format($result);

        $this->assertStringContainsString('Error: Assertion failed', $output);
        $this->assertStringContainsString('Expected: right', $output);
        $this->assertStringContainsString('Actual:   wrong', $output);
    }

    #[Test]
    public function single_line_code_context(): void
    {
        $result = $this->makeResult('$x = 42;', 'err');
        $output = $this->formatter->format($result);

        $this->assertStringContainsString('10 | $x = 42;', $output);
    }

    #[Test]
    public function diff_lines_are_indented(): void
    {
        $result = $this->makeResult(
            'echo "x";',
            null,
            'x',
            'y',
            "- y\n+ x",
        );
        $output = $this->formatter->format($result);

        $this->assertStringContainsString('  Diff:', $output);
        $this->assertStringContainsString('    - y', $output);
        $this->assertStringContainsString('    + x', $output);
    }
}
