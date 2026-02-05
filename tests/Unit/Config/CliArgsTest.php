<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Config;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Config\CliArgs;

final class CliArgsTest extends TestCase
{
    #[Test]
    public function parses_positional_file_arguments(): void
    {
        $args = CliArgs::parse(['doctest', 'docs/', 'README.md']);

        $this->assertSame(['docs/', 'README.md'], $args->files);
    }

    #[Test]
    public function parses_filter_option(): void
    {
        $args = CliArgs::parse(['doctest', '--filter=basic']);

        $this->assertSame('basic', $args->filter);
    }

    #[Test]
    public function parses_exclude_option(): void
    {
        $args = CliArgs::parse(['doctest', '--exclude=vendor']);

        $this->assertSame('vendor', $args->exclude);
    }

    #[Test]
    public function parses_dry_run_flag(): void
    {
        $args = CliArgs::parse(['doctest', '--dry-run']);

        $this->assertTrue($args->dryRun);
    }

    #[Test]
    public function parses_stop_on_failure_flag(): void
    {
        $args = CliArgs::parse(['doctest', '--stop-on-failure']);

        $this->assertTrue($args->stopOnFailure);
    }

    #[Test]
    public function parses_verbosity_v(): void
    {
        $args = CliArgs::parse(['doctest', '-v']);

        $this->assertSame(1, $args->verbosity);
    }

    #[Test]
    public function parses_verbosity_vv(): void
    {
        $args = CliArgs::parse(['doctest', '-vv']);

        $this->assertSame(2, $args->verbosity);
    }

    #[Test]
    public function parses_verbosity_vvv(): void
    {
        $args = CliArgs::parse(['doctest', '-vvv']);

        $this->assertSame(3, $args->verbosity);
    }

    #[Test]
    public function parses_config_path(): void
    {
        $args = CliArgs::parse(['doctest', '--config=/path/to/doctest.php']);

        $this->assertSame('/path/to/doctest.php', $args->configPath);
    }

    #[Test]
    public function defaults_when_no_arguments(): void
    {
        $args = CliArgs::parse(['doctest']);

        $this->assertSame([], $args->files);
        $this->assertNull($args->filter);
        $this->assertNull($args->exclude);
        $this->assertFalse($args->dryRun);
        $this->assertFalse($args->stopOnFailure);
        $this->assertSame(0, $args->verbosity);
        $this->assertNull($args->configPath);
    }

    #[Test]
    public function ignores_unknown_flags(): void
    {
        $args = CliArgs::parse(['doctest', '--unknown', '--foo=bar', 'docs/']);

        $this->assertSame(['docs/'], $args->files);
    }
}
