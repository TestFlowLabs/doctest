<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BinDoctestTest extends TestCase
{
    private string $binPath;

    protected function setUp(): void
    {
        $this->binPath = __DIR__ . '/../../bin/doctest';
    }

    #[Test]
    public function bin_doctest_file_exists(): void
    {
        $this->assertFileExists($this->binPath);
    }

    #[Test]
    public function bin_doctest_is_executable(): void
    {
        $this->assertTrue(is_executable($this->binPath));
    }

    #[Test]
    public function bin_doctest_exits_with_zero(): void
    {
        $fixture = __DIR__ . '/../Fixtures/basic.md';
        exec(PHP_BINARY . ' ' . escapeshellarg($this->binPath) . ' ' . escapeshellarg($fixture) . ' 2>&1', $output, $exitCode);
        $this->assertSame(0, $exitCode);
    }
}
