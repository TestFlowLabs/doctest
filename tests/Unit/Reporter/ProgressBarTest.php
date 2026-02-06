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

    $this->makePassingResult = function (int $line = 1): ExecutionResult {
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
    };
});
test('shows progress count when total set', function (): void {
    $reporter = new ConsoleReporter($this->output);
    $reporter->setTotalBlocks(5);

    $reporter->reportResult(($this->makePassingResult)());

    $content = $this->output->fetch();

    expect($content)->toMatch('/\[1\/5\]/');
});
test('progress count increments', function (): void {
    $reporter = new ConsoleReporter($this->output);
    $reporter->setTotalBlocks(3);

    $reporter->reportResult(($this->makePassingResult)(1));
    $reporter->reportResult(($this->makePassingResult)(5));

    $content = $this->output->fetch();

    $this->assertStringContainsString('[1/3]', $content);
    $this->assertStringContainsString('[2/3]', $content);
});
test('no progress when total not set', function (): void {
    $reporter = new ConsoleReporter($this->output);

    $reporter->reportResult(($this->makePassingResult)());

    $content = $this->output->fetch();

    $this->assertDoesNotMatchRegularExpression('/\[\d+\/\d+\]/', $content);
});
test('progress works with buffered output', function (): void {
    $reporter = new ConsoleReporter($this->output);
    $reporter->setTotalBlocks(2);

    $reporter->reportResult(($this->makePassingResult)());
    $reporter->reportResult(($this->makePassingResult)(5));

    $content = $this->output->fetch();

    $this->assertStringContainsString('[1/2]', $content);
    $this->assertStringContainsString('[2/2]', $content);
});
