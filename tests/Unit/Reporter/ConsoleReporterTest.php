<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Reporter;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\Executor\ExecutionResult;
use TestFlowLabs\DocTest\Reporter\ConsoleReporter;

final class ConsoleReporterTest extends TestCase
{
    private BufferedOutput $output;

    protected function setUp(): void
    {
        $this->output = new BufferedOutput();
    }

    private function getOutput(): string
    {
        return $this->output->fetch();
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
    public function reports_passing_result(): void
    {
        $reporter = new ConsoleReporter($this->output);
        $reporter->reportResult($this->makeResult(passed: true));

        $this->assertStringContainsString('PASS', $this->getOutput());
    }

    #[Test]
    public function reports_failing_result_with_error(): void
    {
        $reporter = new ConsoleReporter($this->output);
        $reporter->reportResult($this->makeResult(passed: false, error: 'Something failed'));

        $output = $this->getOutput();
        $this->assertStringContainsString('FAIL', $output);
        $this->assertStringContainsString('Something failed', $output);
    }

    #[Test]
    public function reports_skipped_result(): void
    {
        $reporter = new ConsoleReporter($this->output);
        $reporter->reportResult($this->makeResult(passed: true, skipped: true));

        $this->assertStringContainsString('SKIP', $this->getOutput());
    }

    #[Test]
    public function reports_file_header(): void
    {
        $reporter = new ConsoleReporter($this->output);
        $reporter->reportFile('docs/example.md');

        $this->assertStringContainsString('docs/example.md', $this->getOutput());
    }

    #[Test]
    public function reports_summary_statistics(): void
    {
        $reporter = new ConsoleReporter($this->output);
        $results = [
            $this->makeResult(passed: true),
            $this->makeResult(passed: true),
            $this->makeResult(passed: false, error: 'fail'),
            $this->makeResult(passed: true, skipped: true),
        ];
        $reporter->reportSummary($results, 1.5);

        $output = $this->getOutput();
        $this->assertStringContainsString('4', $output);
        $this->assertStringContainsString('2', $output);
        $this->assertStringContainsString('1', $output);
    }

    #[Test]
    public function uses_symfony_formatting_tags(): void
    {
        $this->output->setDecorated(true);
        $reporter = new ConsoleReporter($this->output);
        $reporter->reportResult($this->makeResult(passed: true));

        $output = $this->getOutput();
        $this->assertStringContainsString('[PASS]', $output);
    }

    #[Test]
    public function shows_duration_at_verbosity_verbose(): void
    {
        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $reporter = new ConsoleReporter($this->output);
        $reporter->reportResult($this->makeResult(passed: true));

        $output = $this->getOutput();
        $this->assertMatchesRegularExpression('/\[\d+\.\d+s\]/', $output);
    }

    #[Test]
    public function hides_duration_at_normal_verbosity(): void
    {
        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $reporter = new ConsoleReporter($this->output);
        $reporter->reportResult($this->makeResult(passed: true));

        $output = $this->getOutput();
        $this->assertDoesNotMatchRegularExpression('/\[\d+\.\d+s\]/', $output);
    }

    #[Test]
    public function shows_source_at_very_verbose(): void
    {
        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $reporter = new ConsoleReporter($this->output);
        $reporter->reportResult($this->makeResult(passed: false, error: 'fail'));

        $output = $this->getOutput();
        $this->assertStringContainsString('Source:', $output);
        $this->assertStringContainsString('echo "test"', $output);
    }
}
