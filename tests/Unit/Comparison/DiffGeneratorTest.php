<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Comparison;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Comparison\DiffGenerator;

final class DiffGeneratorTest extends TestCase
{
    private DiffGenerator $diff;

    protected function setUp(): void
    {
        $this->diff = new DiffGenerator();
    }

    #[Test]
    public function single_line_diff(): void
    {
        $result = $this->diff->generate("hello\n", "world\n");

        $this->assertStringContainsString('- hello', $result);
        $this->assertStringContainsString('+ world', $result);
    }

    #[Test]
    public function multi_line_diff(): void
    {
        $result = $this->diff->generate("line1\nline2\n", "line1\nchanged\n");

        $this->assertStringContainsString('- line2', $result);
        $this->assertStringContainsString('+ changed', $result);
    }

    #[Test]
    public function addition_only(): void
    {
        $result = $this->diff->generate("line1\n", "line1\nline2\n");

        $this->assertStringContainsString('+ line2', $result);
    }

    #[Test]
    public function removal_only(): void
    {
        $result = $this->diff->generate("line1\nline2\n", "line1\n");

        $this->assertStringContainsString('- line2', $result);
    }

    #[Test]
    public function identical_strings_produce_empty_diff(): void
    {
        $result = $this->diff->generate("same\n", "same\n");

        $this->assertSame('', $result);
    }

    #[Test]
    public function mixed_changes(): void
    {
        $result = $this->diff->generate("a\nb\nc\n", "a\nB\nc\n");

        $this->assertStringContainsString('- b', $result);
        $this->assertStringContainsString('+ B', $result);
        $this->assertStringNotContainsString('- a', $result);
        $this->assertStringNotContainsString('- c', $result);
    }
}
