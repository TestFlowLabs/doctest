<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Executor\ExecutionResult;
use TestFlowLabs\DocTest\Reporter\ConsoleReporter;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

beforeEach(function (): void {
    $this->output = new BufferedOutput();

    $this->getOutput = (fn (): string => $this->output->fetch());

    $this->makeResult = function (bool $passed, float $duration = 0.12, ?string $error = null): ExecutionResult {
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
    };
});
test('verbose shows execution time', function (): void {
    $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
    $reporter = new ConsoleReporter($this->output);
    $reporter->reportResult(($this->makeResult)(passed: true, duration: 0.12));

    $output = ($this->getOutput)();
    $this->assertStringContainsString('0.12s', $output);
});
test('very verbose shows source code on failure', function (): void {
    $this->output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
    $reporter = new ConsoleReporter($this->output);
    $reporter->reportResult(($this->makeResult)(passed: false, error: 'Failed'));

    $output = ($this->getOutput)();
    $this->assertStringContainsString('echo "hello"', $output);
});
test('normal shows timing', function (): void {
    $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
    $reporter = new ConsoleReporter($this->output);
    $reporter->reportResult(($this->makeResult)(passed: true, duration: 0.12));

    $output = ($this->getOutput)();
    $this->assertStringContainsString('0.12s', $output);
});
test('normal does not show full source on failure', function (): void {
    $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
    $reporter = new ConsoleReporter($this->output);
    $reporter->reportResult(($this->makeResult)(passed: false, error: 'Failed'));

    $output = ($this->getOutput)();
    $this->assertStringNotContainsString('Source:', $output);
    $this->assertStringNotContainsString('// Output: hello', $output);
});
