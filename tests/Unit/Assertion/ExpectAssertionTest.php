<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Assertion;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Assertion\Assertion;
use TestFlowLabs\DocTest\Assertion\ExpectAssertion;

final class ExpectAssertionTest extends TestCase
{
    #[Test]
    public function implements_assertion_interface(): void
    {
        $assertion = new ExpectAssertion('$x === 5', 10);

        $this->assertInstanceOf(Assertion::class, $assertion);
    }

    #[Test]
    public function type_returns_expect(): void
    {
        $assertion = new ExpectAssertion('$x === 5', 10);

        $this->assertSame('expect', $assertion->type());
    }

    #[Test]
    public function line_returns_correct_value(): void
    {
        $assertion = new ExpectAssertion('$x === 5', 42);

        $this->assertSame(42, $assertion->line());
    }

    #[Test]
    public function expression_property_is_accessible(): void
    {
        $assertion = new ExpectAssertion('count($items) > 0', 7);

        $this->assertSame('count($items) > 0', $assertion->expression);
    }
}
