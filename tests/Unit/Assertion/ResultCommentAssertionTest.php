<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Assertion;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\Assertion\Assertion;
use TestFlowLabs\DocTest\Assertion\ResultCommentAssertion;

final class ResultCommentAssertionTest extends TestCase
{
    #[Test]
    public function implements_assertion_interface(): void
    {
        $assertion = new ResultCommentAssertion('$x', 'true', 1);

        $this->assertInstanceOf(Assertion::class, $assertion);
    }

    #[Test]
    public function returns_result_comment_type(): void
    {
        $assertion = new ResultCommentAssertion('$x', 'true', 1);

        $this->assertSame('result_comment', $assertion->type());
    }

    #[Test]
    public function stores_expression(): void
    {
        $assertion = new ResultCommentAssertion('$state->matches(\'green\')', 'true', 5);

        $this->assertSame('$state->matches(\'green\')', $assertion->expression);
    }

    #[Test]
    public function stores_expected_value(): void
    {
        $assertion = new ResultCommentAssertion('$x', '42', 3);

        $this->assertSame('42', $assertion->expectedValue);
    }

    #[Test]
    public function stores_line_number(): void
    {
        $assertion = new ResultCommentAssertion('$x', 'true', 10);

        $this->assertSame(10, $assertion->line());
    }
}
