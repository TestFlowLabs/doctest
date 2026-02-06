<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Assertion;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Assertion\OutputContainsAssertion;

final class OutputContainsAssertionTest extends TestCase
{
    #[Test]
    public function type_returns_output_contains(): void
    {
        $assertion = new OutputContainsAssertion('hello', 1);

        $this->assertSame('output_contains', $assertion->type());
    }

    #[Test]
    public function has_expected_substring(): void
    {
        $assertion = new OutputContainsAssertion('world', 5);

        $this->assertSame('world', $assertion->expected);
    }

    #[Test]
    public function line_returns_correct_line(): void
    {
        $assertion = new OutputContainsAssertion('test', 42);

        $this->assertSame(42, $assertion->line());
    }
}
