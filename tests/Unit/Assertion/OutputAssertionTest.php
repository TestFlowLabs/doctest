<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Assertion;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Assertion\Assertion;
use TestFlowLabs\DocTest\Assertion\OutputAssertion;

final class OutputAssertionTest extends TestCase
{
    #[Test]
    public function implements_assertion_interface(): void
    {
        $assertion = new OutputAssertion('Hello', 5);

        $this->assertInstanceOf(Assertion::class, $assertion);
    }

    #[Test]
    public function type_returns_output(): void
    {
        $assertion = new OutputAssertion('Hello', 5);

        $this->assertSame('output', $assertion->type());
    }

    #[Test]
    public function line_returns_correct_value(): void
    {
        $assertion = new OutputAssertion('Hello', 42);

        $this->assertSame(42, $assertion->line());
    }

    #[Test]
    public function expected_property_is_accessible(): void
    {
        $assertion = new OutputAssertion('Hello, World!', 10);

        $this->assertSame('Hello, World!', $assertion->expected);
    }

    #[Test]
    public function handles_multiline_expected(): void
    {
        $expected = "Line 1\nLine 2\nLine 3";
        $assertion = new OutputAssertion($expected, 15);

        $this->assertSame($expected, $assertion->expected);
    }
}
