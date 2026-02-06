<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\Config\DocTestConfig;

final class DocTestConfigTest extends TestCase
{
    #[Test]
    public function uses_defaults_for_missing_keys(): void
    {
        $config = DocTestConfig::fromArray([]);

        $this->assertSame(['docs', 'README.md'], $config->paths);
        $this->assertSame([], $config->exclude);
        $this->assertSame(30, $config->timeout);
        $this->assertSame('256M', $config->memoryLimit);
        $this->assertFalse($config->stopOnFailure);
        $this->assertSame(0, $config->verbosity);
        $this->assertTrue($config->normalizeWhitespace);
        $this->assertTrue($config->trimTrailing);
    }

    #[Test]
    public function loads_from_array_with_all_keys(): void
    {
        $config = DocTestConfig::fromArray([
            'paths'     => ['src'],
            'exclude'   => ['docs/archive/*'],
            'execution' => [
                'timeout'         => 60,
                'memory_limit'    => '512M',
                'stop_on_failure' => true,
            ],
            'output' => [
                'normalize_whitespace' => false,
                'trim_trailing'        => false,
            ],
        ]);

        $this->assertSame(['src'], $config->paths);
        $this->assertSame(['docs/archive/*'], $config->exclude);
        $this->assertSame(60, $config->timeout);
        $this->assertSame('512M', $config->memoryLimit);
        $this->assertTrue($config->stopOnFailure);
        $this->assertFalse($config->normalizeWhitespace);
        $this->assertFalse($config->trimTrailing);
    }

    #[Test]
    public function loads_from_php_file(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'doctest-config-');

        try {
            file_put_contents($tmpFile, "<?php\nreturn ['paths' => ['custom']];\n");

            $config = DocTestConfig::load($tmpFile);

            $this->assertSame(['custom'], $config->paths);
        } finally {
            @unlink($tmpFile);
        }
    }

    #[Test]
    public function returns_default_config_when_no_file_exists(): void
    {
        $config = DocTestConfig::load('/nonexistent/path/doctest.php');

        $this->assertSame(['docs', 'README.md'], $config->paths);
    }

    #[Test]
    public function config_merges_with_defaults(): void
    {
        $config = DocTestConfig::fromArray([
            'execution' => ['timeout' => 120],
        ]);

        $this->assertSame(120, $config->timeout);
        $this->assertSame('256M', $config->memoryLimit);
        $this->assertFalse($config->stopOnFailure);
    }

    #[Test]
    public function reporters_config_defaults(): void
    {
        $config = DocTestConfig::fromArray([]);

        $this->assertTrue($config->reporterConsole);
        $this->assertNull($config->reporterJunit);
        $this->assertNull($config->reporterJson);
    }

    #[Test]
    public function reporters_config_with_file_paths(): void
    {
        $config = DocTestConfig::fromArray([
            'reporters' => [
                'console' => true,
                'junit'   => 'build/doctest.xml',
                'json'    => 'build/doctest.json',
            ],
        ]);

        $this->assertTrue($config->reporterConsole);
        $this->assertSame('build/doctest.xml', $config->reporterJunit);
        $this->assertSame('build/doctest.json', $config->reporterJson);
    }

    #[Test]
    public function exclude_patterns_loaded(): void
    {
        $config = DocTestConfig::fromArray([
            'exclude' => ['docs/archive/*', 'docs/**/*draft*.md'],
        ]);

        $this->assertSame(['docs/archive/*', 'docs/**/*draft*.md'], $config->exclude);
    }

    // --- Edge cases ---

    #[Test]
    public function wrong_type_timeout_falls_back_to_default(): void
    {
        $config = DocTestConfig::fromArray([
            'execution' => ['timeout' => 'not_int'],
        ]);

        $this->assertSame(30, $config->timeout);
    }

    #[Test]
    public function wrong_type_memory_limit_falls_back_to_default(): void
    {
        $config = DocTestConfig::fromArray([
            'execution' => ['memory_limit' => 42],
        ]);

        $this->assertSame('256M', $config->memoryLimit);
    }

    #[Test]
    public function load_returns_defaults_when_file_returns_non_array(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'doctest-config-');

        try {
            file_put_contents($tmpFile, "<?php\nreturn 'not an array';\n");

            $config = DocTestConfig::load($tmpFile);

            $this->assertSame(['docs', 'README.md'], $config->paths);
        } finally {
            @unlink($tmpFile);
        }
    }

    #[Test]
    public function stop_on_failure_from_top_level_key(): void
    {
        $config = DocTestConfig::fromArray([
            'stop_on_failure' => true,
        ]);

        $this->assertTrue($config->stopOnFailure);
    }

    #[Test]
    public function filter_and_dry_run_loaded(): void
    {
        $config = DocTestConfig::fromArray([
            'filter'  => 'example.md',
            'dry_run' => true,
        ]);

        $this->assertSame('example.md', $config->filter);
        $this->assertTrue($config->dryRun);
    }

    #[Test]
    public function non_array_execution_section_uses_defaults(): void
    {
        $config = DocTestConfig::fromArray([
            'execution' => 'invalid',
        ]);

        $this->assertSame(30, $config->timeout);
        $this->assertSame('256M', $config->memoryLimit);
    }
}
