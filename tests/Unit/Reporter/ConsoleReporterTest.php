<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Reporter;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\Executor\ExecutionResult;
use TestFlowLabs\DocTest\Reporter\ConsoleReporter;

final class ConsoleReporterTest extends TestCase
{
    /** @var resource */
    private $stream;

    protected function setUp(): void
    {
        $stream = fopen('php://memory', 'r+');
        $this->assertIsResource($stream);
        $this->stream = $stream;
    }

    protected function tearDown(): void
    {
        fclose($this->stream);
    }

    private function getOutput(): string
    {
        rewind($this->stream);

        return stream_get_contents($this->stream) ?: '';
    }

    private function makeResult(bool $passed, bool $skipped = false, ?string $error = null, ?string $diff = null): ExecutionResult
    {
        $block = new CodeBlock(
            file: 'docs/test.md',
            startLine: 1,
            rawCode: 'echo "test";',
            executableCode: 'echo "test";',
            attributes: new Attributes(),
            assertions: [],
        );

        return new ExecutionResult(
            passed: $passed,
            codeBlock: $block,
            actualOutput: $passed ? null : 'wrong',
            expectedOutput: $passed ? null : 'right',
            diff: $diff,
            error: $error,
            skipped: $skipped,
        );
    }

    #[Test]
    public function reports_passing_result_with_checkmark(): void
    {
        $reporter = new ConsoleReporter($this->stream, colors: false);
        $reporter->reportResult($this->makeResult(passed: true));

        $this->assertStringContainsString('PASS', $this->getOutput());
    }

    #[Test]
    public function reports_failing_result_with_error(): void
    {
        $reporter = new ConsoleReporter($this->stream, colors: false);
        $reporter->reportResult($this->makeResult(passed: false, error: 'Something failed'));

        $output = $this->getOutput();
        $this->assertStringContainsString('FAIL', $output);
        $this->assertStringContainsString('Something failed', $output);
    }

    #[Test]
    public function reports_skipped_result(): void
    {
        $reporter = new ConsoleReporter($this->stream, colors: false);
        $reporter->reportResult($this->makeResult(passed: true, skipped: true));

        $this->assertStringContainsString('SKIP', $this->getOutput());
    }

    #[Test]
    public function reports_file_header(): void
    {
        $reporter = new ConsoleReporter($this->stream, colors: false);
        $reporter->reportFile('docs/example.md');

        $this->assertStringContainsString('docs/example.md', $this->getOutput());
    }

    #[Test]
    public function reports_summary_statistics(): void
    {
        $reporter = new ConsoleReporter($this->stream, colors: false);
        $results = [
            $this->makeResult(passed: true),
            $this->makeResult(passed: true),
            $this->makeResult(passed: false, error: 'fail'),
            $this->makeResult(passed: true, skipped: true),
        ];
        $reporter->reportSummary($results, 1.5);

        $output = $this->getOutput();
        $this->assertStringContainsString('4', $output); // total blocks
        $this->assertStringContainsString('2', $output); // passed
        $this->assertStringContainsString('1', $output); // failed
    }

    #[Test]
    public function no_ansi_codes_when_colors_disabled(): void
    {
        $reporter = new ConsoleReporter($this->stream, colors: false);
        $reporter->reportResult($this->makeResult(passed: true));

        $output = $this->getOutput();
        $this->assertStringNotContainsString("\033[", $output);
    }
}
