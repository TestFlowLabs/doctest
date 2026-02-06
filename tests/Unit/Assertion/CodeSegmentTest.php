<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Assertion;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Assertion\CodeSegment;
use TestFlowLabs\DocTest\Assertion\OutputAssertion;

final class CodeSegmentTest extends TestCase
{
    #[Test]
    public function construction_with_code_and_assertion(): void
    {
        $assertion = new OutputAssertion('Hello', 5);
        $segment = new CodeSegment('echo "Hello";', $assertion);

        $this->assertSame('echo "Hello";', $segment->code);
        $this->assertSame($assertion, $segment->outputAssertion);
    }

    #[Test]
    public function construction_with_null_assertion(): void
    {
        $segment = new CodeSegment('$x = 42;');

        $this->assertSame('$x = 42;', $segment->code);
        $this->assertNull($segment->outputAssertion);
    }

    #[Test]
    public function assertion_properties_are_accessible(): void
    {
        $assertion = new OutputAssertion('World', 10);
        $segment = new CodeSegment('echo "World";', $assertion);

        $this->assertSame('World', $segment->outputAssertion->expected);
        $this->assertSame(10, $segment->outputAssertion->line());
    }
}
