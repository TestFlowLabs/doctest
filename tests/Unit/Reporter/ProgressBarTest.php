<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Reporter;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\Executor\ExecutionResult;
use TestFlowLabs\DocTest\Reporter\ConsoleReporter;

final class ProgressBarTest extends TestCase
{
    private AssertionParser $parser;

    protected function setUp(): void
    {
        $this->parser = new AssertionParser();
    }

    private function makePassingResult(int $line = 1): ExecutionResult
    {
        $parsed = $this->parser->parse('echo "ok";');

        return new ExecutionResult(
            passed: true,
            codeBlock: new CodeBlock(
                file: 'test.md',
                startLine: $line,
                rawCode: 'echo "ok";',
                executableCode: $parsed->executableCode,
                attributes: new Attributes(),
                assertions: $parsed->assertions,
            ),
        );
    }

    #[Test]
    public function shows_progress_count_when_total_set(): void
    {
        $output = fopen('php://memory', 'rw');
        $reporter = new ConsoleReporter($output, colors: false);
        $reporter->setTotalBlocks(5);

        $reporter->reportResult($this->makePassingResult());

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        $this->assertMatchesRegularExpression('/\[1\/5\]/', $content);
    }

    #[Test]
    public function progress_count_increments(): void
    {
        $output = fopen('php://memory', 'rw');
        $reporter = new ConsoleReporter($output, colors: false);
        $reporter->setTotalBlocks(3);

        $reporter->reportResult($this->makePassingResult(1));
        $reporter->reportResult($this->makePassingResult(5));

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        $this->assertStringContainsString('[1/3]', $content);
        $this->assertStringContainsString('[2/3]', $content);
    }

    #[Test]
    public function no_progress_when_total_not_set(): void
    {
        $output = fopen('php://memory', 'rw');
        $reporter = new ConsoleReporter($output, colors: false);

        $reporter->reportResult($this->makePassingResult());

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        $this->assertDoesNotMatchRegularExpression('/\[\d+\/\d+\]/', $content);
    }

    #[Test]
    public function progress_works_with_non_tty_output(): void
    {
        $output = fopen('php://memory', 'rw');
        $reporter = new ConsoleReporter($output, colors: false);
        $reporter->setTotalBlocks(2);

        $reporter->reportResult($this->makePassingResult());
        $reporter->reportResult($this->makePassingResult(5));

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        // Should still show progress even on non-TTY (no ANSI cursor movement, just inline)
        $this->assertStringContainsString('[1/2]', $content);
        $this->assertStringContainsString('[2/2]', $content);
    }
}
