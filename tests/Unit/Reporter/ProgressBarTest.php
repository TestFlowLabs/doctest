<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Reporter;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Executor\ExecutionResult;
use TestFlowLabs\DocTest\Reporter\ConsoleReporter;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use Symfony\Component\Console\Output\BufferedOutput;

final class ProgressBarTest extends TestCase
{
    private AssertionParser $parser;
    private BufferedOutput $output;

    protected function setUp(): void
    {
        $this->parser = new AssertionParser();
        $this->output = new BufferedOutput();
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
                assertions: [],
            ),
        );
    }

    #[Test]
    public function shows_progress_count_when_total_set(): void
    {
        $reporter = new ConsoleReporter($this->output);
        $reporter->setTotalBlocks(5);

        $reporter->reportResult($this->makePassingResult());

        $content = $this->output->fetch();

        $this->assertMatchesRegularExpression('/\[1\/5\]/', $content);
    }

    #[Test]
    public function progress_count_increments(): void
    {
        $reporter = new ConsoleReporter($this->output);
        $reporter->setTotalBlocks(3);

        $reporter->reportResult($this->makePassingResult(1));
        $reporter->reportResult($this->makePassingResult(5));

        $content = $this->output->fetch();

        $this->assertStringContainsString('[1/3]', $content);
        $this->assertStringContainsString('[2/3]', $content);
    }

    #[Test]
    public function no_progress_when_total_not_set(): void
    {
        $reporter = new ConsoleReporter($this->output);

        $reporter->reportResult($this->makePassingResult());

        $content = $this->output->fetch();

        $this->assertDoesNotMatchRegularExpression('/\[\d+\/\d+\]/', $content);
    }

    #[Test]
    public function progress_works_with_buffered_output(): void
    {
        $reporter = new ConsoleReporter($this->output);
        $reporter->setTotalBlocks(2);

        $reporter->reportResult($this->makePassingResult());
        $reporter->reportResult($this->makePassingResult(5));

        $content = $this->output->fetch();

        $this->assertStringContainsString('[1/2]', $content);
        $this->assertStringContainsString('[2/2]', $content);
    }
}
