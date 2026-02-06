<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Executor;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\Executor\ProcessRunner;

final class ProcessRunnerTest extends TestCase
{
    private ProcessRunner $runner;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->runner = new ProcessRunner(timeout: 5, memoryLimit: '128M');
        $this->tmpDir = sys_get_temp_dir().'/doctest-test-'.uniqid();
        mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), glob($this->tmpDir.'/*') ?: []);
        rmdir($this->tmpDir);
    }

    private function writeTmpFile(string $code): string
    {
        $path = $this->tmpDir.'/test_'.uniqid().'.php';
        file_put_contents($path, "<?php\n".$code);

        return $path;
    }

    #[Test]
    public function runs_php_file_and_captures_stdout(): void
    {
        $file   = $this->writeTmpFile('echo "Hello World";');
        $result = $this->runner->run($file);

        $this->assertSame('Hello World', $result->stdout);
    }

    #[Test]
    public function captures_stderr(): void
    {
        $file   = $this->writeTmpFile('fwrite(STDERR, "error output");');
        $result = $this->runner->run($file);

        $this->assertSame('error output', $result->stderr);
    }

    #[Test]
    public function returns_exit_code_zero_for_valid_script(): void
    {
        $file   = $this->writeTmpFile('echo "ok";');
        $result = $this->runner->run($file);

        $this->assertSame(0, $result->exitCode);
    }

    #[Test]
    public function returns_non_zero_exit_code_for_failing_script(): void
    {
        $file   = $this->writeTmpFile('exit(1);');
        $result = $this->runner->run($file);

        $this->assertSame(1, $result->exitCode);
    }

    #[Test]
    public function measures_execution_duration(): void
    {
        $file   = $this->writeTmpFile('echo "fast";');
        $result = $this->runner->run($file);

        $this->assertGreaterThan(0.0, $result->duration);
        $this->assertLessThan(5.0, $result->duration);
    }

    #[Test]
    public function enforces_timeout(): void
    {
        $runner = new ProcessRunner(timeout: 1, memoryLimit: '128M');
        $file   = $this->writeTmpFile('sleep(10); echo "done";');
        $result = $runner->run($file);

        $this->assertNotSame(0, $result->exitCode, 'Should fail due to timeout');
        $this->assertGreaterThan(0.9, $result->duration, 'Should run close to timeout duration');
        $this->assertLessThan(5.0, $result->duration, 'Should not run full sleep duration');
    }

    #[Test]
    public function uses_php_binary(): void
    {
        $file   = $this->writeTmpFile('echo PHP_BINARY;');
        $result = $this->runner->run($file);

        $this->assertSame(PHP_BINARY, $result->stdout);
    }

    #[Test]
    public function rejects_invalid_memory_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ProcessRunner(timeout: 5, memoryLimit: 'invalid');
    }

    #[Test]
    public function accepts_valid_memory_limits(): void
    {
        $this->expectNotToPerformAssertions();

        new ProcessRunner(timeout: 5, memoryLimit: '128M');
        new ProcessRunner(timeout: 5, memoryLimit: '256m');
        new ProcessRunner(timeout: 5, memoryLimit: '1G');
        new ProcessRunner(timeout: 5, memoryLimit: '1024K');
        new ProcessRunner(timeout: 5, memoryLimit: '-1');
    }

    // --- Timeout edge cases ---

    #[Test]
    public function timeout_returns_exit_code_137(): void
    {
        $runner = new ProcessRunner(timeout: 1, memoryLimit: '128M');
        $file   = $this->writeTmpFile('while(true) { usleep(1000); }');
        $result = $runner->run($file);

        $this->assertSame(137, $result->exitCode);
    }

    #[Test]
    public function fast_process_completes_before_timeout(): void
    {
        $runner = new ProcessRunner(timeout: 5, memoryLimit: '128M');
        $file   = $this->writeTmpFile('echo "quick";');
        $result = $runner->run($file);

        $this->assertSame(0, $result->exitCode);
        $this->assertSame('quick', $result->stdout);
        $this->assertLessThan(2.0, $result->duration);
    }

    // --- Output edge cases ---

    #[Test]
    public function handles_large_stdout(): void
    {
        $file   = $this->writeTmpFile('echo str_repeat("X", 100_000);');
        $result = $this->runner->run($file);

        $this->assertSame(0, $result->exitCode);
        $this->assertSame(100_000, strlen($result->stdout));
    }

    #[Test]
    public function handles_empty_output(): void
    {
        $file   = $this->writeTmpFile('$x = 1;');
        $result = $this->runner->run($file);

        $this->assertSame(0, $result->exitCode);
        $this->assertSame('', $result->stdout);
        $this->assertSame('', $result->stderr);
    }

    #[Test]
    public function captures_interleaved_stdout_and_stderr(): void
    {
        $file   = $this->writeTmpFile('echo "out"; fwrite(STDERR, "err");');
        $result = $this->runner->run($file);

        $this->assertSame('out', $result->stdout);
        $this->assertSame('err', $result->stderr);
    }

    // --- Exit code edge cases ---

    #[Test]
    public function captures_custom_exit_code(): void
    {
        $file   = $this->writeTmpFile('exit(42);');
        $result = $this->runner->run($file);

        $this->assertSame(42, $result->exitCode);
    }

    #[Test]
    public function handles_fatal_error_exit_code(): void
    {
        $file   = $this->writeTmpFile('$x = new NonExistentClass();');
        $result = $this->runner->run($file);

        $this->assertNotSame(0, $result->exitCode);
    }

    // --- Memory limit edge cases ---

    #[Test]
    public function rejects_memory_limit_without_unit_and_not_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ProcessRunner(timeout: 5, memoryLimit: 'abc');
    }

    #[Test]
    public function accepts_zero_memory_limit(): void
    {
        $this->expectNotToPerformAssertions();

        new ProcessRunner(timeout: 5, memoryLimit: '0');
    }

    #[Test]
    public function rejects_memory_limit_with_whitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ProcessRunner(timeout: 5, memoryLimit: '128 M');
    }

    #[Test]
    public function memory_limit_enforced_at_runtime(): void
    {
        $runner = new ProcessRunner(timeout: 5, memoryLimit: '8M');
        $file   = $this->writeTmpFile('$a = str_repeat("X", 100_000_000);');
        $result = $runner->run($file);

        $this->assertNotSame(0, $result->exitCode);
    }

    // --- File handling ---

    #[Test]
    public function handles_nonexistent_file(): void
    {
        $result = $this->runner->run('/tmp/nonexistent_doctest_file.php');

        $this->assertNotSame(0, $result->exitCode);
        $this->assertGreaterThan(0.0, $result->duration);
    }
}
