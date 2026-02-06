<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Config\DocTestConfig;
use TestFlowLabs\DocTest\DocTest;

final class EndToEndTest extends TestCase
{
    /** @var resource */
    private $output;

    private string $fixturesDir;

    protected function setUp(): void
    {
        $stream = fopen('php://memory', 'r+');
        $this->assertIsResource($stream);
        $this->output = $stream;
        $this->fixturesDir = dirname(__DIR__) . '/Fixtures';
    }

    protected function tearDown(): void
    {
        fclose($this->output);
    }

    private function runDocTest(DocTestConfig $config): int
    {
        $docTest = new DocTest($config, $this->output);

        return $docTest->run();
    }

    private function getOutput(): string
    {
        rewind($this->output);

        return stream_get_contents($this->output) ?: '';
    }

    #[Test]
    public function runs_simple_output_test_and_passes(): void
    {
        $config = DocTestConfig::fromArray([
            'paths' => [$this->fixturesDir . '/basic.md'],
        ]);

        $this->assertSame(0, $this->runDocTest($config));
    }

    #[Test]
    public function runs_failing_output_test_and_reports_failure(): void
    {
        $config = DocTestConfig::fromArray([
            'paths' => [$this->fixturesDir . '/failing-output.md'],
        ]);

        $exitCode = $this->runDocTest($config);
        $output = $this->getOutput();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('FAIL', $output);
    }

    #[Test]
    public function runs_ignored_block_and_skips_it(): void
    {
        $config = DocTestConfig::fromArray([
            'paths' => [$this->fixturesDir . '/ignore-block.md'],
        ]);

        $exitCode = $this->runDocTest($config);
        $output = $this->getOutput();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('SKIP', $output);
        $this->assertStringContainsString('PASS', $output);
    }

    #[Test]
    public function runs_expect_assertions(): void
    {
        $config = DocTestConfig::fromArray([
            'paths' => [$this->fixturesDir . '/expect.md'],
        ]);

        $this->assertSame(0, $this->runDocTest($config));
    }

    #[Test]
    public function runs_multiple_files(): void
    {
        $config = DocTestConfig::fromArray([
            'paths' => [
                $this->fixturesDir . '/basic.md',
                $this->fixturesDir . '/expect.md',
            ],
        ]);

        $exitCode = $this->runDocTest($config);

        $this->assertSame(0, $exitCode);
    }

    #[Test]
    public function respects_dry_run(): void
    {
        $config = DocTestConfig::fromArray([
            'paths' => [$this->fixturesDir . '/basic.md'],
            'dry_run' => true,
        ]);

        $exitCode = $this->runDocTest($config);
        $output = $this->getOutput();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('SKIP', $output);
    }

    #[Test]
    public function respects_stop_on_failure(): void
    {
        $tempFile = sys_get_temp_dir() . '/doctest_stop_e2e_' . uniqid() . '.md';
        file_put_contents($tempFile, "```php\necho \"wrong\";\n// Output: right\n```\n\n```php\necho \"ok\";\n// Output: ok\n```\n");

        $config = DocTestConfig::fromArray([
            'paths' => [$tempFile],
            'stop_on_failure' => true,
        ]);

        $exitCode = $this->runDocTest($config);
        $output = $this->getOutput();

        unlink($tempFile);
        $this->assertSame(1, $exitCode);
        // Should only have one FAIL, not a second PASS (stopped early)
        $this->assertSame(1, substr_count($output, 'FAIL'));
        $this->assertStringNotContainsString('PASS', $output);
    }

    #[Test]
    public function exit_code_0_when_all_pass(): void
    {
        $config = DocTestConfig::fromArray([
            'paths' => [$this->fixturesDir . '/basic.md'],
        ]);

        $this->assertSame(0, $this->runDocTest($config));
    }

    #[Test]
    public function exit_code_1_when_any_fail(): void
    {
        $config = DocTestConfig::fromArray([
            'paths' => [$this->fixturesDir . '/failing-output.md'],
        ]);

        $this->assertSame(1, $this->runDocTest($config));
    }

    #[Test]
    public function exit_code_3_when_no_tests_found(): void
    {
        $config = DocTestConfig::fromArray([
            'paths' => [$this->fixturesDir . '/no-php.md'],
        ]);

        $this->assertSame(3, $this->runDocTest($config));
    }

    #[Test]
    public function handles_empty_markdown_file(): void
    {
        $config = DocTestConfig::fromArray([
            'paths' => [$this->fixturesDir . '/empty.md'],
        ]);

        $this->assertSame(3, $this->runDocTest($config));
    }

    #[Test]
    public function handles_markdown_with_no_php_blocks(): void
    {
        $config = DocTestConfig::fromArray([
            'paths' => [$this->fixturesDir . '/no-php.md'],
        ]);

        $this->assertSame(3, $this->runDocTest($config));
    }
}
