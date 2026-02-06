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

final class VerbosityTest extends TestCase
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

    private function makeResult(bool $passed, float $duration = 0.12, ?string $error = null): ExecutionResult
    {
        $block = new CodeBlock(
            file: 'docs/test.md',
            startLine: 42,
            rawCode: "echo \"hello\";\n// Output: hello",
            executableCode: 'echo "hello";',
            attributes: new Attributes(),
            assertions: [],
        );

        return new ExecutionResult(
            passed: $passed,
            codeBlock: $block,
            error: $error,
            duration: $duration,
        );
    }

    #[Test]
    public function verbose_shows_execution_time(): void
    {
        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $reporter = new ConsoleReporter($this->output);
        $reporter->reportResult($this->makeResult(passed: true, duration: 0.12));

        $output = $this->getOutput();
        $this->assertStringContainsString('0.12s', $output);
    }

    #[Test]
    public function very_verbose_shows_source_code_on_failure(): void
    {
        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $reporter = new ConsoleReporter($this->output);
        $reporter->reportResult($this->makeResult(passed: false, error: 'Failed'));

        $output = $this->getOutput();
        $this->assertStringContainsString('echo "hello"', $output);
    }

    #[Test]
    public function normal_does_not_show_timing(): void
    {
        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $reporter = new ConsoleReporter($this->output);
        $reporter->reportResult($this->makeResult(passed: true, duration: 0.12));

        $output = $this->getOutput();
        $this->assertStringNotContainsString('0.12s', $output);
    }

    #[Test]
    public function normal_does_not_show_source_on_failure(): void
    {
        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $reporter = new ConsoleReporter($this->output);
        $reporter->reportResult($this->makeResult(passed: false, error: 'Failed'));

        $output = $this->getOutput();
        $this->assertStringNotContainsString('echo "hello"', $output);
    }
}
