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
    public function pass_result_shows_checkmark_symbol(): void
    {
        $reporter = new ConsoleReporter($this->output);
        $reporter->reportResult($this->makeResult(passed: true));

        $output = $this->getOutput();
        $this->assertStringContainsString('✔', $output);
        $this->assertStringNotContainsString('[PASS]', $output);
    }

    #[Test]
    public function fail_result_shows_cross_symbol(): void
    {
        $reporter = new ConsoleReporter($this->output);
        $reporter->reportResult($this->makeResult(passed: false, error: 'Something failed'));

        $output = $this->getOutput();
        $this->assertStringContainsString('✖', $output);
        $this->assertStringNotContainsString('[FAIL]', $output);
        $this->assertStringContainsString('Something failed', $output);
    }

    #[Test]
    public function skip_result_shows_skip_symbol(): void
    {
        $reporter = new ConsoleReporter($this->output);
        $reporter->reportResult($this->makeResult(passed: true, skipped: true));

        $output = $this->getOutput();
        $this->assertStringContainsString('⊘', $output);
        $this->assertStringNotContainsString('[SKIP]', $output);
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
        $this->assertStringContainsString('✔', $output);
    }

    #[Test]
    public function shows_duration_at_normal_verbosity(): void
    {
        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $reporter = new ConsoleReporter($this->output);
        $reporter->reportResult($this->makeResult(passed: true));

        $output = $this->getOutput();
        $this->assertMatchesRegularExpression('/\d+\.\d+s/', $output);
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

    #[Test]
    public function pass_result_shows_first_code_line(): void
    {
        $reporter = new ConsoleReporter($this->output);
        $block = new CodeBlock(
            file: 'docs/test.md',
            startLine: 42,
            rawCode: "\$name = 'World';\necho \"Hello, {\$name}!\";",
            executableCode: 'echo "test";',
            attributes: new Attributes(),
            assertions: [],
        );
        $result = new ExecutionResult(passed: true, codeBlock: $block);
        $reporter->reportResult($result);

        $output = $this->getOutput();
        $this->assertStringContainsString('$name = \'World\';', $output);
        $this->assertStringContainsString(':42', $output);
        $this->assertStringNotContainsString('Line 42', $output);
    }

    #[Test]
    public function skip_result_shows_first_code_line(): void
    {
        $reporter = new ConsoleReporter($this->output);
        $block = new CodeBlock(
            file: 'docs/test.md',
            startLine: 10,
            rawCode: "// This is skipped\necho 'skip';",
            executableCode: '',
            attributes: new Attributes(),
            assertions: [],
        );
        $result = new ExecutionResult(passed: true, codeBlock: $block, skipped: true);
        $reporter->reportResult($result);

        $output = $this->getOutput();
        $this->assertStringContainsString('// This is skipped', $output);
        $this->assertStringContainsString(':10', $output);
        $this->assertStringNotContainsString('Line 10', $output);
    }

    #[Test]
    public function long_first_line_is_truncated(): void
    {
        $reporter = new ConsoleReporter($this->output);
        $longLine = str_repeat('x', 80);
        $block = new CodeBlock(
            file: 'docs/test.md',
            startLine: 1,
            rawCode: $longLine,
            executableCode: 'echo "test";',
            attributes: new Attributes(),
            assertions: [],
        );
        $result = new ExecutionResult(passed: true, codeBlock: $block);
        $reporter->reportResult($result);

        $output = $this->getOutput();
        $this->assertStringContainsString('...', $output);
        $this->assertLessThan(strlen($longLine), strlen($output));
    }
}
