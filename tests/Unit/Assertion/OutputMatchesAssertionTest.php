<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Assertion;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
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

    #[Test]
    public function parser_detects_output_matches(): void
    {
        $parser = new AssertionParser();
        $result = $parser->parse("echo \"Order #1234\";\n// OutputMatches: /Order #\\d{4}/");

        $this->assertCount(1, $result->assertions);
        $this->assertInstanceOf(OutputMatchesAssertion::class, $result->assertions[0]);
    }
}
