<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Reporter;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\Executor\ExecutionResult;
use TestFlowLabs\DocTest\Reporter\ConsoleReporter;

final class VerbosityTest extends TestCase
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
    public function verbosity_1_shows_execution_time(): void
    {
        $reporter = new ConsoleReporter($this->stream, colors: false, verbosity: 1);
        $reporter->reportResult($this->makeResult(passed: true, duration: 0.12));

        $output = $this->getOutput();
        $this->assertStringContainsString('0.12s', $output);
    }

    #[Test]
    public function verbosity_2_shows_source_code_on_failure(): void
    {
        $reporter = new ConsoleReporter($this->stream, colors: false, verbosity: 2);
        $reporter->reportResult($this->makeResult(passed: false, error: 'Failed'));

        $output = $this->getOutput();
        $this->assertStringContainsString('echo "hello"', $output);
    }

    #[Test]
    public function verbosity_0_does_not_show_timing(): void
    {
        $reporter = new ConsoleReporter($this->stream, colors: false, verbosity: 0);
        $reporter->reportResult($this->makeResult(passed: true, duration: 0.12));

        $output = $this->getOutput();
        $this->assertStringNotContainsString('0.12s', $output);
    }

    #[Test]
    public function verbosity_0_does_not_show_source_on_failure(): void
    {
        $reporter = new ConsoleReporter($this->stream, colors: false, verbosity: 0);
        $reporter->reportResult($this->makeResult(passed: false, error: 'Failed'));

        $output = $this->getOutput();
        $this->assertStringNotContainsString('echo "hello"', $output);
    }
}
