<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Executor\ExecutionResult;
use TestFlowLabs\DocTest\Reporter\ConsoleReporter;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use TestFlowLabs\DocTest\Assertion\AssertionResultDetail;

beforeEach(function (): void {
    $this->output = new BufferedOutput();

    $this->getOutput = (fn (): string => $this->output->fetch());

    $this->makeResult = function (bool $passed, bool $skipped = false, ?string $error = null, ?string $diff = null): ExecutionResult {
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
    };
});
test('pass result shows checkmark symbol', function (): void {
    $reporter = new ConsoleReporter($this->output);
    $reporter->reportResult(($this->makeResult)(passed: true));

    $output = ($this->getOutput)();
    $this->assertStringContainsString('✔', $output);
    $this->assertStringNotContainsString('[PASS]', $output);
});
test('fail result shows cross symbol', function (): void {
    $reporter = new ConsoleReporter($this->output);
    $reporter->reportResult(($this->makeResult)(passed: false, error: 'Something failed'));

    $output = ($this->getOutput)();
    $this->assertStringContainsString('✖', $output);
    $this->assertStringNotContainsString('[FAIL]', $output);
    $this->assertStringContainsString('Something failed', $output);
});
test('skip result shows skip symbol', function (): void {
    $reporter = new ConsoleReporter($this->output);
    $reporter->reportResult(($this->makeResult)(passed: true, skipped: true));

    $output = ($this->getOutput)();
    $this->assertStringContainsString('⊘', $output);
    $this->assertStringNotContainsString('[SKIP]', $output);
});
test('reports file header', function (): void {
    $reporter = new ConsoleReporter($this->output);
    $reporter->reportFile('docs/example.md');

    $this->assertStringContainsString('docs/example.md', ($this->getOutput)());
});
test('reports summary statistics', function (): void {
    $reporter = new ConsoleReporter($this->output);
    $results  = [
        ($this->makeResult)(passed: true),
        ($this->makeResult)(passed: true),
        ($this->makeResult)(passed: false, error: 'fail'),
        ($this->makeResult)(passed: true, skipped: true),
    ];
    $reporter->reportSummary($results, 1.5);

    $output = ($this->getOutput)();
    $this->assertStringContainsString('4', $output);
    $this->assertStringContainsString('2', $output);
    $this->assertStringContainsString('1', $output);
});
test('uses symfony formatting tags', function (): void {
    $this->output->setDecorated(true);
    $reporter = new ConsoleReporter($this->output);
    $reporter->reportResult(($this->makeResult)(passed: true));

    $output = ($this->getOutput)();
    $this->assertStringContainsString('✔', $output);
});
test('shows duration at normal verbosity', function (): void {
    $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
    $reporter = new ConsoleReporter($this->output);
    $reporter->reportResult(($this->makeResult)(passed: true));

    $output = ($this->getOutput)();
    expect($output)->toMatch('/\d+\.\d+s/');
});
test('shows source at very verbose', function (): void {
    $this->output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
    $reporter = new ConsoleReporter($this->output);
    $reporter->reportResult(($this->makeResult)(passed: false, error: 'fail'));

    $output = ($this->getOutput)();
    $this->assertStringContainsString('Source:', $output);
    $this->assertStringContainsString('echo "test"', $output);
});
test('pass result shows first code line', function (): void {
    $reporter = new ConsoleReporter($this->output);
    $block    = new CodeBlock(
        file: 'docs/test.md',
        startLine: 42,
        rawCode: "\$name = 'World';\necho \"Hello, {\$name}!\";",
        executableCode: 'echo "test";',
        attributes: new Attributes(),
        assertions: [],
    );
    $result = new ExecutionResult(passed: true, codeBlock: $block);
    $reporter->reportResult($result);

    $output = ($this->getOutput)();
    $this->assertStringContainsString('$name = \'World\';', $output);
    $this->assertStringContainsString(':42', $output);
    $this->assertStringNotContainsString('Line 42', $output);
});
test('skip result shows first code line', function (): void {
    $reporter = new ConsoleReporter($this->output);
    $block    = new CodeBlock(
        file: 'docs/test.md',
        startLine: 10,
        rawCode: "// This is skipped\necho 'skip';",
        executableCode: '',
        attributes: new Attributes(),
        assertions: [],
    );
    $result = new ExecutionResult(passed: true, codeBlock: $block, skipped: true);
    $reporter->reportResult($result);

    $output = ($this->getOutput)();
    $this->assertStringContainsString('// This is skipped', $output);
    $this->assertStringContainsString(':10', $output);
    $this->assertStringNotContainsString('Line 10', $output);
});
test('long first line is truncated', function (): void {
    $reporter = new ConsoleReporter($this->output);
    $longLine = str_repeat('x', 80);
    $block    = new CodeBlock(
        file: 'docs/test.md',
        startLine: 1,
        rawCode: $longLine,
        executableCode: 'echo "test";',
        attributes: new Attributes(),
        assertions: [],
    );
    $result = new ExecutionResult(passed: true, codeBlock: $block);
    $reporter->reportResult($result);

    $output = ($this->getOutput)();
    $this->assertStringContainsString('...', $output);
    expect(strlen((string) $output))->toBeLessThan(strlen($longLine));
});
test('verbose shows assertion details for output', function (): void {
    $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
    $reporter = new ConsoleReporter($this->output);

    $block = new CodeBlock(
        file: 'test.md',
        startLine: 1,
        rawCode: 'echo "Hello";',
        executableCode: 'echo "Hello";',
        attributes: new Attributes(),
        assertions: [],
    );

    $result = new ExecutionResult(
        passed: true,
        codeBlock: $block,
        assertionDetails: [
            new AssertionResultDetail(type: 'output', passed: true, expected: 'Hello', actual: 'Hello', line: 2),
        ],
    );

    $reporter->reportResult($result);
    $output = ($this->getOutput)();

    $this->assertStringContainsString('✔', $output);
    $this->assertStringContainsString('output', $output);
    $this->assertStringContainsString('Hello', $output);
});
test('verbose shows assertion details for result comment', function (): void {
    $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
    $reporter = new ConsoleReporter($this->output);

    $block = new CodeBlock(
        file: 'test.md',
        startLine: 1,
        rawCode: '$x = 42; // => 42',
        executableCode: '$x = 42;',
        attributes: new Attributes(),
        assertions: [],
    );

    $result = new ExecutionResult(
        passed: true,
        codeBlock: $block,
        assertionDetails: [
            new AssertionResultDetail(type: 'result_comment', passed: true, expected: '42', actual: '42', line: 1, expression: '$x = 42'),
        ],
    );

    $reporter->reportResult($result);
    $output = ($this->getOutput)();

    $this->assertStringContainsString('$x = 42', $output);
    $this->assertStringContainsString('=> 42', $output);
});
test('verbose shows assertion details for expect', function (): void {
    $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
    $reporter = new ConsoleReporter($this->output);

    $block = new CodeBlock(
        file: 'test.md',
        startLine: 1,
        rawCode: "\$x = 42;\n// Expect: \$x === 42",
        executableCode: '$x = 42;',
        attributes: new Attributes(),
        assertions: [],
    );

    $result = new ExecutionResult(
        passed: true,
        codeBlock: $block,
        assertionDetails: [
            new AssertionResultDetail(type: 'expect', passed: true, expected: '$x === 42', actual: 'true', line: 2, expression: '$x === 42'),
        ],
    );

    $reporter->reportResult($result);
    $output = ($this->getOutput)();

    $this->assertStringContainsString('$x === 42', $output);
});
test('normal verbosity does not show assertion details', function (): void {
    $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
    $reporter = new ConsoleReporter($this->output);

    $block = new CodeBlock(
        file: 'test.md',
        startLine: 1,
        rawCode: '$x = 42; // => 42',
        executableCode: '$x = 42;',
        attributes: new Attributes(),
        assertions: [],
    );

    $result = new ExecutionResult(
        passed: true,
        codeBlock: $block,
        assertionDetails: [
            new AssertionResultDetail(type: 'result_comment', passed: true, expected: '42', actual: '42', line: 1, expression: '$x = 42'),
        ],
    );

    $reporter->reportResult($result);
    $output = ($this->getOutput)();

    // Detail lines are indented with extra spaces — check for the verbose format
    $this->assertStringNotContainsString('       ✔', $output);
});
test('verbose shows failed assertion detail', function (): void {
    $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
    $reporter = new ConsoleReporter($this->output);

    $block = new CodeBlock(
        file: 'test.md',
        startLine: 1,
        rawCode: '$x = 42; // => 99',
        executableCode: '$x = 42;',
        attributes: new Attributes(),
        assertions: [],
    );

    $result = new ExecutionResult(
        passed: false,
        codeBlock: $block,
        error: 'result_comment assertion failed: expected 99 but got 42',
        assertionDetails: [
            new AssertionResultDetail(type: 'result_comment', passed: false, expected: '99', actual: '42', line: 1, expression: '$x = 42'),
        ],
    );

    $reporter->reportResult($result);
    $output = ($this->getOutput)();

    $this->assertStringContainsString('✖', $output);
    $this->assertStringContainsString('99', $output);
    $this->assertStringContainsString('42', $output);
});
