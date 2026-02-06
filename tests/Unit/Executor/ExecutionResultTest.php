<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Executor;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Executor\ExecutionResult;
use TestFlowLabs\DocTest\Assertion\AssertionResultDetail;

final class ExecutionResultTest extends TestCase
{
    private function makeBlock(): CodeBlock
    {
        return new CodeBlock(
            file: 'test.md',
            startLine: 1,
            rawCode: 'echo "Hi";',
            executableCode: 'echo "Hi";',
            attributes: new Attributes(),
            assertions: [],
        );
    }

    #[Test]
    public function construction_with_all_properties(): void
    {
        $block  = $this->makeBlock();
        $result = new ExecutionResult(
            passed: true,
            codeBlock: $block,
            actualOutput: 'Hi',
            expectedOutput: 'Hi',
            diff: null,
            error: null,
            duration: 0.05,
        );

        $this->assertTrue($result->passed);
        $this->assertSame($block, $result->codeBlock);
        $this->assertSame('Hi', $result->actualOutput);
        $this->assertSame('Hi', $result->expectedOutput);
        $this->assertNull($result->diff);
        $this->assertNull($result->error);
        $this->assertSame(0.05, $result->duration);
    }

    #[Test]
    public function defaults_for_optional_params(): void
    {
        $result = new ExecutionResult(
            passed: true,
            codeBlock: $this->makeBlock(),
        );

        $this->assertNull($result->actualOutput);
        $this->assertNull($result->expectedOutput);
        $this->assertNull($result->diff);
        $this->assertNull($result->error);
        $this->assertSame(0.0, $result->duration);
    }

    #[Test]
    public function failed_result_with_diff_and_error(): void
    {
        $result = new ExecutionResult(
            passed: false,
            codeBlock: $this->makeBlock(),
            actualOutput: 'wrong',
            expectedOutput: 'right',
            diff: '- right\n+ wrong',
            error: 'Output mismatch',
            duration: 0.1,
        );

        $this->assertFalse($result->passed);
        $this->assertSame('wrong', $result->actualOutput);
        $this->assertSame('right', $result->expectedOutput);
        $this->assertSame('- right\n+ wrong', $result->diff);
        $this->assertSame('Output mismatch', $result->error);
    }

    #[Test]
    public function skipped_result(): void
    {
        $result = new ExecutionResult(
            passed: true,
            codeBlock: $this->makeBlock(),
            skipped: true,
        );

        $this->assertTrue($result->passed);
        $this->assertTrue($result->skipped);
    }

    #[Test]
    public function assertion_details_defaults_to_empty_array(): void
    {
        $result = new ExecutionResult(
            passed: true,
            codeBlock: $this->makeBlock(),
        );

        $this->assertSame([], $result->assertionDetails);
    }

    #[Test]
    public function stores_assertion_details(): void
    {
        $details = [
            new AssertionResultDetail(type: 'output', passed: true, expected: 'Hi', actual: 'Hi', line: 1),
            new AssertionResultDetail(type: 'result_comment', passed: true, expected: '42', actual: '42', line: 2, expression: '$x = 42'),
        ];

        $result = new ExecutionResult(
            passed: true,
            codeBlock: $this->makeBlock(),
            assertionDetails: $details,
        );

        $this->assertCount(2, $result->assertionDetails);
        $this->assertSame('output', $result->assertionDetails[0]->type);
        $this->assertSame('result_comment', $result->assertionDetails[1]->type);
        $this->assertSame('$x = 42', $result->assertionDetails[1]->expression);
    }
}
