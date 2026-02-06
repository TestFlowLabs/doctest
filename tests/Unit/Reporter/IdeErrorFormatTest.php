<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Reporter;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\Executor\ExecutionResult;
use TestFlowLabs\DocTest\Reporter\ConsoleReporter;

final class IdeErrorFormatTest extends TestCase
{
    private AssertionParser $parser;

    private BufferedOutput $output;

    protected function setUp(): void
    {
        $this->parser = new AssertionParser();
        $this->output = new BufferedOutput();
    }

    private function makeResult(string $file, int $line, ?string $error = null): ExecutionResult
    {
        $parsed = $this->parser->parse('echo "test";');

        return new ExecutionResult(
            passed: false,
            codeBlock: new CodeBlock(
                file: $file,
                startLine: $line,
                rawCode: 'echo "test";',
                executableCode: $parsed->executableCode,
                attributes: new Attributes(),
                assertions: $parsed->assertions,
            ),
            error: $error ?? 'Output mismatch',
        );
    }

    #[Test]
    public function failure_output_includes_file_colon_line_pattern(): void
    {
        $reporter = new ConsoleReporter($this->output);

        $result = $this->makeResult('docs/api.md', 42);
        $reporter->reportResult($result);

        $content = $this->output->fetch();

        $this->assertMatchesRegularExpression('/docs\/api\.md:42/', $content);
    }

    #[Test]
    public function failure_includes_error_message_after_location(): void
    {
        $reporter = new ConsoleReporter($this->output);

        $result = $this->makeResult('test.md', 10, 'Expected "hello" but got "world"');
        $reporter->reportResult($result);

        $content = $this->output->fetch();

        $this->assertStringContainsString('test.md:10', $content);
        $this->assertStringContainsString('Expected "hello" but got "world"', $content);
    }

    #[Test]
    public function passing_result_shows_file_and_line(): void
    {
        $reporter = new ConsoleReporter($this->output);
        $parsed = $this->parser->parse('echo "ok";');

        $result = new ExecutionResult(
            passed: true,
            codeBlock: new CodeBlock(
                file: 'readme.md',
                startLine: 5,
                rawCode: 'echo "ok";',
                executableCode: $parsed->executableCode,
                attributes: new Attributes(),
                assertions: $parsed->assertions,
            ),
        );

        $reporter->reportResult($result);

        $content = $this->output->fetch();

        $this->assertStringContainsString('Line 5', $content);
    }
}
