<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Comparison;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Comparison\OutputComparator;

final class OutputComparatorTest extends TestCase
{
    private OutputComparator $comparator;

    protected function setUp(): void
    {
        $this->comparator = new OutputComparator();
    }

    #[Test]
    public function exact_match_passes(): void
    {
        $result = $this->comparator->compare("Hello\n", "Hello\n");

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function exact_match_fails(): void
    {
        $result = $this->comparator->compare("Hello\n", "World\n");

        $this->assertFalse($result->passed);
    }

    #[Test]
    public function normalized_match_passes_trailing_whitespace(): void
    {
        $result = $this->comparator->compare("Hello   \n", "Hello\n");

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function normalized_match_passes_crlf_vs_lf(): void
    {
        $result = $this->comparator->compare("Hello\r\n", "Hello\n");

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function empty_expected_vs_empty_actual_passes(): void
    {
        $result = $this->comparator->compare('', '');

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function multi_line_comparison(): void
    {
        $result = $this->comparator->compare("line1\nline2\n", "line1\nline2\n");

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function failed_result_includes_normalized_values(): void
    {
        $result = $this->comparator->compare("expected\n", "actual\n");

        $this->assertFalse($result->passed);
        $this->assertSame("expected\n", $result->normalizedExpected);
        $this->assertSame("actual\n", $result->normalizedActual);
    }
}
