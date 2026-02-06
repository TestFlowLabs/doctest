<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Assertion;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\Assertion\OutputMatchesAssertion;

final class OutputMatchesAssertionTest extends TestCase
{
    #[Test]
    public function type_returns_output_matches(): void
    {
        $assertion = new OutputMatchesAssertion('/\d+/', 1);

        $this->assertSame('output_matches', $assertion->type());
    }

    #[Test]
    public function has_pattern(): void
    {
        $assertion = new OutputMatchesAssertion('/Order #\d{4}/', 5);

        $this->assertSame('/Order #\d{4}/', $assertion->pattern);
    }
}
