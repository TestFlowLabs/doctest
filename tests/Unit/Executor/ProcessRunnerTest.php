<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Executor\ProcessRunner;

beforeEach(function (): void {
    $this->runner = new ProcessRunner(timeout: 5, memoryLimit: '128M');
    $this->tmpDir = sys_get_temp_dir().'/doctest-test-'.uniqid();
    mkdir($this->tmpDir);

    $this->writeTmpFile = function (string $code): string {
        $path = $this->tmpDir.'/test_'.uniqid().'.php';
        file_put_contents($path, "<?php\n".$code);

        return $path;
    };
});
afterEach(function (): void {
    array_map(unlink(...), glob($this->tmpDir.'/*') ?: []);
    rmdir($this->tmpDir);
});
test('runs php file and captures stdout', function (): void {
    $file   = ($this->writeTmpFile)('echo "Hello World";');
    $result = $this->runner->run($file);

    expect($result->stdout)->toBe('Hello World');
});
test('captures stderr', function (): void {
    $file   = ($this->writeTmpFile)('fwrite(STDERR, "error output");');
    $result = $this->runner->run($file);

    expect($result->stderr)->toBe('error output');
});
test('returns exit code zero for valid script', function (): void {
    $file   = ($this->writeTmpFile)('echo "ok";');
    $result = $this->runner->run($file);

    expect($result->exitCode)->toBe(0);
});
test('returns non zero exit code for failing script', function (): void {
    $file   = ($this->writeTmpFile)('exit(1);');
    $result = $this->runner->run($file);

    expect($result->exitCode)->toBe(1);
});
test('measures execution duration', function (): void {
    $file   = ($this->writeTmpFile)('echo "fast";');
    $result = $this->runner->run($file);

    expect($result->duration)->toBeGreaterThan(0.0);
    expect($result->duration)->toBeLessThan(5.0);
});
test('enforces timeout', function (): void {
    $runner = new ProcessRunner(timeout: 1, memoryLimit: '128M');
    $file   = ($this->writeTmpFile)('sleep(10); echo "done";');
    $result = $runner->run($file);

    $this->assertNotSame(0, $result->exitCode, 'Should fail due to timeout');
    expect($result->duration)->toBeGreaterThan(0.9, 'Should run close to timeout duration');
    expect($result->duration)->toBeLessThan(5.0, 'Should not run full sleep duration');
});
test('uses php binary', function (): void {
    $file   = ($this->writeTmpFile)('echo PHP_BINARY;');
    $result = $this->runner->run($file);

    expect($result->stdout)->toBe(PHP_BINARY);
});
test('rejects invalid memory limit', function (): void {
    $this->expectException(InvalidArgumentException::class);

    new ProcessRunner(timeout: 5, memoryLimit: 'invalid');
});
test('accepts valid memory limits', function (): void {
    $this->expectNotToPerformAssertions();

    new ProcessRunner(timeout: 5, memoryLimit: '128M');
    new ProcessRunner(timeout: 5, memoryLimit: '256m');
    new ProcessRunner(timeout: 5, memoryLimit: '1G');
    new ProcessRunner(timeout: 5, memoryLimit: '1024K');
    new ProcessRunner(timeout: 5, memoryLimit: '-1');
});
test('timeout returns exit code 137', function (): void {
    $runner = new ProcessRunner(timeout: 1, memoryLimit: '128M');
    $file   = ($this->writeTmpFile)('while(true) { usleep(1000); }');
    $result = $runner->run($file);

    expect($result->exitCode)->toBe(137);
});
test('fast process completes before timeout', function (): void {
    $runner = new ProcessRunner(timeout: 5, memoryLimit: '128M');
    $file   = ($this->writeTmpFile)('echo "quick";');
    $result = $runner->run($file);

    expect($result->exitCode)->toBe(0);
    expect($result->stdout)->toBe('quick');
    expect($result->duration)->toBeLessThan(2.0);
});
test('handles large stdout', function (): void {
    $file   = ($this->writeTmpFile)('echo str_repeat("X", 100_000);');
    $result = $this->runner->run($file);

    expect($result->exitCode)->toBe(0);
    expect(strlen((string) $result->stdout))->toBe(100_000);
});
test('handles empty output', function (): void {
    $file   = ($this->writeTmpFile)('$x = 1;');
    $result = $this->runner->run($file);

    expect($result->exitCode)->toBe(0);
    expect($result->stdout)->toBe('');
    expect($result->stderr)->toBe('');
});
test('captures interleaved stdout and stderr', function (): void {
    $file   = ($this->writeTmpFile)('echo "out"; fwrite(STDERR, "err");');
    $result = $this->runner->run($file);

    expect($result->stdout)->toBe('out');
    expect($result->stderr)->toBe('err');
});
test('captures custom exit code', function (): void {
    $file   = ($this->writeTmpFile)('exit(42);');
    $result = $this->runner->run($file);

    expect($result->exitCode)->toBe(42);
});
test('handles fatal error exit code', function (): void {
    $file   = ($this->writeTmpFile)('$x = new NonExistentClass();');
    $result = $this->runner->run($file);

    $this->assertNotSame(0, $result->exitCode);
});
test('rejects memory limit without unit and not negative', function (): void {
    $this->expectException(InvalidArgumentException::class);

    new ProcessRunner(timeout: 5, memoryLimit: 'abc');
});
test('accepts zero memory limit', function (): void {
    $this->expectNotToPerformAssertions();

    new ProcessRunner(timeout: 5, memoryLimit: '0');
});
test('rejects memory limit with whitespace', function (): void {
    $this->expectException(InvalidArgumentException::class);

    new ProcessRunner(timeout: 5, memoryLimit: '128 M');
});
test('memory limit enforced at runtime', function (): void {
    $runner = new ProcessRunner(timeout: 5, memoryLimit: '8M');
    $file   = ($this->writeTmpFile)('$a = str_repeat("X", 100_000_000);');
    $result = $runner->run($file);

    $this->assertNotSame(0, $result->exitCode);
});
test('handles nonexistent file', function (): void {
    $result = $this->runner->run('/tmp/nonexistent_doctest_file.php');

    $this->assertNotSame(0, $result->exitCode);
    expect($result->duration)->toBeGreaterThan(0.0);
});
