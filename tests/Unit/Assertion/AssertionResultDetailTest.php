<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Assertion;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\Assertion\AssertionResultDetail;

final class AssertionResultDetailTest extends TestCase
{
    #[Test]
    public function stores_all_properties(): void
    {
        $detail = new AssertionResultDetail(
            type: 'output',
            passed: true,
            expected: 'Hello',
            actual: 'Hello',
            line: 5,
        );

        $this->assertSame('output', $detail->type);
        $this->assertTrue($detail->passed);
        $this->assertSame('Hello', $detail->expected);
        $this->assertSame('Hello', $detail->actual);
        $this->assertSame(5, $detail->line);
    }

    #[Test]
    public function stores_failed_assertion(): void
    {
        $detail = new AssertionResultDetail(
            type: 'result_comment',
            passed: false,
            expected: '99',
            actual: '42',
            line: 10,
        );

        $this->assertFalse($detail->passed);
        $this->assertSame('99', $detail->expected);
        $this->assertSame('42', $detail->actual);
    }

    #[Test]
    public function stores_expect_assertion_with_expression(): void
    {
        $detail = new AssertionResultDetail(
            type: 'expect',
            passed: true,
            expected: '$x === 42',
            actual: 'true',
            line: 3,
        );

        $this->assertSame('expect', $detail->type);
        $this->assertSame('$x === 42', $detail->expected);
    }

    #[Test]
    public function has_nullable_expression(): void
    {
        $detail = new AssertionResultDetail(
            type: 'result_comment',
            passed: true,
            expected: '42',
            actual: '42',
            line: 1,
            expression: '$x = 42',
        );

        $this->assertSame('$x = 42', $detail->expression);
    }

    #[Test]
    public function expression_defaults_to_null(): void
    {
        $detail = new AssertionResultDetail(
            type: 'output',
            passed: true,
            expected: 'test',
            actual: 'test',
            line: 1,
        );

        $this->assertNull($detail->expression);
    }
}
