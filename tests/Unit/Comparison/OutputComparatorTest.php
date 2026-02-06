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

    #[Test]
    public function wildcard_any_matches(): void
    {
        $result = $this->comparator->compare('Hello from {{any}}', 'Hello from localhost');

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function wildcard_int_matches(): void
    {
        $result = $this->comparator->compare('Count: {{int}}', 'Count: 42');

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function wildcard_float_matches(): void
    {
        $result = $this->comparator->compare('Value: {{float}}', 'Value: 3.14');

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function wildcard_date_matches(): void
    {
        $result = $this->comparator->compare('Date: {{date}}', 'Date: 2026-02-06');

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function wildcard_time_matches(): void
    {
        $result = $this->comparator->compare('Time: {{time}}', 'Time: 14:30:00');

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function wildcard_uuid_matches(): void
    {
        $result = $this->comparator->compare('ID: {{uuid}}', 'ID: 550e8400-e29b-41d4-a716-446655440000');

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function wildcard_multiline_matches(): void
    {
        $result = $this->comparator->compare("Header\n{{...}}\nFooter", "Header\nsome dynamic\ncontent here\nFooter");

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function wildcard_without_match_fails(): void
    {
        $result = $this->comparator->compare('Count: {{int}}', 'Count: abc');

        $this->assertFalse($result->passed);
    }

    #[Test]
    public function compare_json_passes_with_same_structure(): void
    {
        $result = $this->comparator->compareJson('{"a": 1, "b": 2}', '{"a":1,"b":2}');

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function compare_json_passes_with_different_key_order(): void
    {
        $result = $this->comparator->compareJson('{"a": 1, "b": 2}', '{"b":2,"a":1}');

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function compare_json_passes_with_nested_different_key_order(): void
    {
        $result = $this->comparator->compareJson(
            '{"user": {"name": "Alice", "age": 30}, "roles": ["admin"]}',
            '{"roles":["admin"],"user":{"age":30,"name":"Alice"}}',
        );

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function compare_json_fails_with_different_values(): void
    {
        $result = $this->comparator->compareJson('{"a": 1}', '{"a":2}');

        $this->assertFalse($result->passed);
    }

    #[Test]
    public function compare_json_fails_with_different_array_order(): void
    {
        $result = $this->comparator->compareJson('["a", "b"]', '["b","a"]');

        $this->assertFalse($result->passed);
    }

    #[Test]
    public function compare_json_fails_with_invalid_expected(): void
    {
        $result = $this->comparator->compareJson('not json', '{"a":1}');

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('Expected JSON is invalid', $result->normalizedExpected);
    }

    #[Test]
    public function compare_json_fails_with_invalid_actual(): void
    {
        $result = $this->comparator->compareJson('{"a": 1}', 'not json');

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('Actual JSON output is invalid', $result->normalizedActual);
    }
}
