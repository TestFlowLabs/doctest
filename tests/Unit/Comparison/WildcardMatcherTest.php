<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Comparison;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\Comparison\WildcardMatcher;

final class WildcardMatcherTest extends TestCase
{
    private WildcardMatcher $matcher;

    protected function setUp(): void
    {
        $this->matcher = new WildcardMatcher();
    }

    #[Test]
    public function any_matches_non_empty_string(): void
    {
        $this->assertTrue($this->matcher->matches('Hello World', '{{any}}'));
        $this->assertFalse($this->matcher->matches('', '{{any}}'));
    }

    #[Test]
    public function int_matches_integers(): void
    {
        $this->assertTrue($this->matcher->matches('42', '{{int}}'));
        $this->assertTrue($this->matcher->matches('-7', '{{int}}'));
        $this->assertFalse($this->matcher->matches('3.14', '{{int}}'));
    }

    #[Test]
    public function float_matches_floats(): void
    {
        $this->assertTrue($this->matcher->matches('3.14', '{{float}}'));
        $this->assertTrue($this->matcher->matches('-0.5', '{{float}}'));
        $this->assertTrue($this->matcher->matches('42', '{{float}}'));
    }

    #[Test]
    public function uuid_matches_uuid_v4(): void
    {
        $this->assertTrue($this->matcher->matches('550e8400-e29b-41d4-a716-446655440000', '{{uuid}}'));
        $this->assertFalse($this->matcher->matches('not-a-uuid', '{{uuid}}'));
    }

    #[Test]
    public function datetime_matches_iso_8601(): void
    {
        $this->assertTrue($this->matcher->matches('2024-01-15T10:30:00', '{{datetime}}'));
        $this->assertTrue($this->matcher->matches('2024-01-15T10:30:00+00:00', '{{datetime}}'));
    }

    #[Test]
    public function date_matches_ymd(): void
    {
        $this->assertTrue($this->matcher->matches('2024-01-15', '{{date}}'));
        $this->assertFalse($this->matcher->matches('01-15-2024', '{{date}}'));
    }

    #[Test]
    public function time_matches_his(): void
    {
        $this->assertTrue($this->matcher->matches('10:30:00', '{{time}}'));
        $this->assertFalse($this->matcher->matches('10:30', '{{time}}'));
    }

    #[Test]
    public function ellipsis_matches_any_text_including_newlines(): void
    {
        $this->assertTrue($this->matcher->matches("line1\nline2\nline3", '{{...}}'));
        $this->assertTrue($this->matcher->matches('', '{{...}}'));
    }

    #[Test]
    public function mixed_wildcards_and_literal(): void
    {
        $this->assertTrue($this->matcher->matches('ID: 42, Name: John', 'ID: {{int}}, Name: {{any}}'));
        $this->assertFalse($this->matcher->matches('ID: abc, Name: John', 'ID: {{int}}, Name: {{any}}'));
    }

    #[Test]
    public function no_wildcards_means_exact_match(): void
    {
        $this->assertTrue($this->matcher->matches('hello', 'hello'));
        $this->assertFalse($this->matcher->matches('hello', 'world'));
    }

    #[Test]
    public function unknown_placeholder_treated_as_literal(): void
    {
        $this->assertTrue($this->matcher->matches('{{unknown}}', '{{unknown}}'));
        $this->assertFalse($this->matcher->matches('hello', '{{unknown}}'));
    }
}
