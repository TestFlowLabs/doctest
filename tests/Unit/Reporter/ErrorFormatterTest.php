<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Reporter\ErrorFormatter;
use TestFlowLabs\DocTest\Executor\ExecutionResult;

beforeEach(function (): void {
    $this->formatter = new ErrorFormatter();

    $this->makeResult = function (string $code, ?string $error = null, ?string $actualOutput = null, ?string $expectedOutput = null, ?string $diff = null): ExecutionResult {
        $block = new CodeBlock(
            file: 'docs/example.md',
            startLine: 10,
            rawCode: $code,
            executableCode: $code,
            attributes: new Attributes(),
            assertions: [],
        );

        return new ExecutionResult(
            passed: false,
            codeBlock: $block,
            actualOutput: $actualOutput,
            expectedOutput: $expectedOutput,
            diff: $diff,
            error: $error,
        );
    };
});
test('formats with file line location header', function (): void {
    $result = ($this->makeResult)('echo "test";', 'Some error');
    $output = $this->formatter->format($result);

    $this->assertStringContainsString('docs/example.md:10', $output);
});
test('shows code context with line numbers', function (): void {
    $result = ($this->makeResult)("echo \"hello\";\necho \"world\";", 'Error occurred');
    $output = $this->formatter->format($result);

    $this->assertStringContainsString('10 |', $output);
    $this->assertStringContainsString('echo "hello"', $output);
});
test('shows error message', function (): void {
    $result = ($this->makeResult)('echo "test";', 'Undefined variable $x');
    $output = $this->formatter->format($result);

    $this->assertStringContainsString('Undefined variable $x', $output);
});
test('formats assertion failure with expected vs actual', function (): void {
    $result = ($this->makeResult)('echo "actual";', null, 'actual', 'expected', "- expected\n+ actual");
    $output = $this->formatter->format($result);

    $this->assertStringContainsString('Expected', $output);
    $this->assertStringContainsString('Actual', $output);
});
test('formats timeout error', function (): void {
    $result = ($this->makeResult)('sleep(100);', 'Process timed out after 5 seconds');
    $output = $this->formatter->format($result);

    $this->assertStringContainsString('timed out', $output);
});
test('formats diff output', function (): void {
    $result = ($this->makeResult)('echo "wrong";', null, 'wrong', 'right', "- right\n+ wrong");
    $output = $this->formatter->format($result);

    $this->assertStringContainsString('- right', $output);
    $this->assertStringContainsString('+ wrong', $output);
});
test('shows context window around error', function (): void {
    $code   = "line1;\nline2;\nline3;\nline4;\nline5;";
    $result = ($this->makeResult)($code, 'Error on line3');
    $output = $this->formatter->format($result);

    $this->assertStringContainsString('line1', $output);
    $this->assertStringContainsString('line5', $output);
});
test('formats result with no error and no assertion failure', function (): void {
    $result = ($this->makeResult)('$x = 1;');
    $output = $this->formatter->format($result);

    $this->assertStringContainsString('docs/example.md:10', $output);
    $this->assertStringContainsString('$x = 1;', $output);
    $this->assertStringNotContainsString('Error:', $output);
    $this->assertStringNotContainsString('Expected:', $output);
});
test('line numbers increment for multiline code', function (): void {
    $code   = "line1;\nline2;\nline3;";
    $result = ($this->makeResult)($code, 'err');
    $output = $this->formatter->format($result);

    $this->assertStringContainsString('10 | line1;', $output);
    $this->assertStringContainsString('11 | line2;', $output);
    $this->assertStringContainsString('12 | line3;', $output);
});
test('only expected without actual does not show comparison', function (): void {
    $result = ($this->makeResult)('echo "test";', null, null, 'expected value');
    $output = $this->formatter->format($result);

    $this->assertStringNotContainsString('Expected:', $output);
    $this->assertStringNotContainsString('Actual:', $output);
});
test('error and assertion failure both shown', function (): void {
    $result = ($this->makeResult)('echo "wrong";', 'Assertion failed', 'wrong', 'right');
    $output = $this->formatter->format($result);

    $this->assertStringContainsString('Error: Assertion failed', $output);
    $this->assertStringContainsString('Expected: right', $output);
    $this->assertStringContainsString('Actual:   wrong', $output);
});
test('single line code context', function (): void {
    $result = ($this->makeResult)('$x = 42;', 'err');
    $output = $this->formatter->format($result);

    $this->assertStringContainsString('10 | $x = 42;', $output);
});
test('diff lines are indented', function (): void {
    $result = ($this->makeResult)('echo "x";', null, 'x', 'y', "- y\n+ x");
    $output = $this->formatter->format($result);

    $this->assertStringContainsString('  Diff:', $output);
    $this->assertStringContainsString('    - y', $output);
    $this->assertStringContainsString('    + x', $output);
});
