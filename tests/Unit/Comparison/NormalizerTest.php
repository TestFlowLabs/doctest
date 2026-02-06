<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Comparison;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Comparison\Normalizer;

final class NormalizerTest extends TestCase
{
    private Normalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new Normalizer();
    }

    #[Test]
    public function converts_crlf_to_lf(): void
    {
        $this->assertSame("line1\nline2\n", $this->normalizer->normalize("line1\r\nline2\r\n"));
    }

    #[Test]
    public function removes_trailing_whitespace_from_lines(): void
    {
        $this->assertSame("hello\nworld\n", $this->normalizer->normalize("hello   \nworld\t\n"));
    }

    #[Test]
    public function removes_leading_blank_lines(): void
    {
        $this->assertSame("content\n", $this->normalizer->normalize("\n\n\ncontent\n"));
    }

    #[Test]
    public function removes_trailing_blank_lines(): void
    {
        $this->assertSame("content\n", $this->normalizer->normalize("content\n\n\n"));
    }

    #[Test]
    public function normalizes_to_single_trailing_newline(): void
    {
        $this->assertSame("hello\n", $this->normalizer->normalize("hello"));
        $this->assertSame("hello\n", $this->normalizer->normalize("hello\n\n\n"));
    }

    #[Test]
    public function handles_empty_string(): void
    {
        $this->assertSame('', $this->normalizer->normalize(''));
    }

    #[Test]
    public function preserves_internal_blank_lines(): void
    {
        $this->assertSame("first\n\nsecond\n", $this->normalizer->normalize("first\n\nsecond"));
    }

    #[Test]
    public function preserves_internal_indentation(): void
    {
        $this->assertSame("  indented\n    more\n", $this->normalizer->normalize("  indented\n    more"));
    }
}
