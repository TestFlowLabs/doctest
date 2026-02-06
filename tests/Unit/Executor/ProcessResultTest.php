<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Executor;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\Executor\ProcessResult;

final class ProcessResultTest extends TestCase
{
    #[Test]
    public function construction_with_all_properties(): void
    {
        $result = new ProcessResult(
            stdout: 'Hello World',
            stderr: '{"results":[]}',
            exitCode: 0,
            duration: 1.23,
        );

        $this->assertSame('Hello World', $result->stdout);
        $this->assertSame('{"results":[]}', $result->stderr);
        $this->assertSame(0, $result->exitCode);
        $this->assertSame(1.23, $result->duration);
    }

    #[Test]
    public function with_empty_stdout_and_stderr(): void
    {
        $result = new ProcessResult(
            stdout: '',
            stderr: '',
            exitCode: 0,
            duration: 0.01,
        );

        $this->assertSame('', $result->stdout);
        $this->assertSame('', $result->stderr);
    }

    #[Test]
    public function with_nonzero_exit_code(): void
    {
        $result = new ProcessResult(
            stdout: '',
            stderr: 'Fatal error',
            exitCode: 255,
            duration: 0.5,
        );

        $this->assertSame(255, $result->exitCode);
    }
}
