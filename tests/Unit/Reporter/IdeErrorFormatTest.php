<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Executor\ExecutionResult;
use TestFlowLabs\DocTest\Reporter\ConsoleReporter;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    $this->parser = new AssertionParser();
    $this->output = new BufferedOutput();

    $this->makeResult = function (string $file, int $line, ?string $error = null): ExecutionResult {
        $parsed = $this->parser->parse('echo "test";');

        return new ExecutionResult(
            passed: false,
            codeBlock: new CodeBlock(
                file: $file,
                startLine: $line,
                rawCode: 'echo "test";',
                executableCode: $parsed->executableCode,
                attributes: new Attributes(),
                assertions: [],
            ),
            error: $error ?? 'Output mismatch',
        );
    };
});
test('failure output includes line number', function (): void {
    $reporter = new ConsoleReporter($this->output);

    $result = ($this->makeResult)('docs/api.md', 42);
    $reporter->reportResult($result);

    $content = $this->output->fetch();

    $this->assertStringContainsString(':42', $content);
    $this->assertStringContainsString('✖', $content);
});
test('failure includes error message', function (): void {
    $reporter = new ConsoleReporter($this->output);

    $result = ($this->makeResult)('test.md', 10, 'Expected "hello" but got "world"');
    $reporter->reportResult($result);

    $content = $this->output->fetch();

    $this->assertStringContainsString(':10', $content);
    $this->assertStringContainsString('Expected "hello" but got "world"', $content);
});
test('passing result shows file and line', function (): void {
    $reporter = new ConsoleReporter($this->output);
    $parsed   = $this->parser->parse('echo "ok";');

    $result = new ExecutionResult(
        passed: true,
        codeBlock: new CodeBlock(
            file: 'readme.md',
            startLine: 5,
            rawCode: 'echo "ok";',
            executableCode: $parsed->executableCode,
            attributes: new Attributes(),
            assertions: [],
        ),
    );

    $reporter->reportResult($result);

    $content = $this->output->fetch();

    $this->assertStringContainsString(':5', $content);
});
