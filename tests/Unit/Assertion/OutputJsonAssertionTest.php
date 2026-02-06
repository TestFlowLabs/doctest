<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Assertion;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Assertion\OutputJsonAssertion;

final class OutputJsonAssertionTest extends TestCase
{
    #[Test]
    public function type_returns_output_json(): void
    {
        $assertion = new OutputJsonAssertion('{"key": "value"}', 1);

        $this->assertSame('output_json', $assertion->type());
    }

    #[Test]
    public function has_expected_json(): void
    {
        $assertion = new OutputJsonAssertion('{"status": "ok"}', 5);

        $this->assertSame('{"status": "ok"}', $assertion->expectedJson);
    }
}
