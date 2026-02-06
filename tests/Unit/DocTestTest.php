<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use TestFlowLabs\DocTest\Config\DocTestConfig;
use TestFlowLabs\DocTest\DocTest;

final class DocTestTest extends TestCase
{
    private BufferedOutput $output;

    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->output = new BufferedOutput();
        $this->fixturesDir = dirname(__DIR__) . '/Fixtures';
    }

    private function makeDocTest(DocTestConfig $config): DocTest
    {
        return new DocTest($config, $this->output);
    }

    #[Test]
    public function run_returns_0_when_all_blocks_pass(): void
    {
        $config = DocTestConfig::fromArray([
            'paths' => [$this->fixturesDir . '/basic.md'],
        ]);

        $docTest = $this->makeDocTest($config);
        $exitCode = $docTest->run();

        $this->assertSame(0, $exitCode);
    }

    #[Test]
    public function run_returns_1_when_any_block_fails(): void
    {
        // Create a temp file with a failing assertion
        $tempFile = sys_get_temp_dir() . '/doctest_failing_' . uniqid() . '.md';
        file_put_contents($tempFile, "```php\necho \"wrong\";\n// Output: right\n```\n");

        $config = DocTestConfig::fromArray([
            'paths' => [$tempFile],
        ]);

        $docTest = $this->makeDocTest($config);
        $exitCode = $docTest->run();

        unlink($tempFile);
        $this->assertSame(1, $exitCode);
    }

    #[Test]
    public function run_returns_3_when_no_tests_found(): void
    {
        $config = DocTestConfig::fromArray([
            'paths' => [$this->fixturesDir . '/no-php.md'],
        ]);

        $docTest = $this->makeDocTest($config);
        $exitCode = $docTest->run();

        $this->assertSame(3, $exitCode);
    }

    #[Test]
    public function respects_dry_run(): void
    {
        $config = DocTestConfig::fromArray([
            'paths' => [$this->fixturesDir . '/basic.md'],
            'dry_run' => true,
        ]);

        $docTest = $this->makeDocTest($config);
        $exitCode = $docTest->run();

        $output = $this->output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('basic.md', $output);
    }

    #[Test]
    public function respects_stop_on_failure(): void
    {
        // Create a file with a failing block followed by a passing block
        $tempFile = sys_get_temp_dir() . '/doctest_stop_' . uniqid() . '.md';
        file_put_contents($tempFile, "```php\necho \"wrong\";\n// Output: right\n```\n\n```php\necho \"ok\";\n// Output: ok\n```\n");

        $config = DocTestConfig::fromArray([
            'paths' => [$tempFile],
            'stop_on_failure' => true,
        ]);

        $docTest = $this->makeDocTest($config);
        $exitCode = $docTest->run();

        unlink($tempFile);
        $this->assertSame(1, $exitCode);
    }

    #[Test]
    public function test_file_returns_execution_results(): void
    {
        $config = DocTestConfig::fromArray([
            'paths' => [$this->fixturesDir],
        ]);

        $docTest = $this->makeDocTest($config);
        $results = $docTest->testFile($this->fixturesDir . '/basic.md');

        $this->assertNotEmpty($results);
        $this->assertCount(4, $results); // 4 PHP blocks in basic.md
    }

    #[Test]
    public function test_all_processes_all_discovered_files(): void
    {
        $config = DocTestConfig::fromArray([
            'paths' => [$this->fixturesDir],
        ]);

        $docTest = $this->makeDocTest($config);
        $results = $docTest->testAll();

        // Should have results from multiple fixture files
        $this->assertNotEmpty($results);
    }
}
