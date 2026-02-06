<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Console;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use TestFlowLabs\DocTest\Console\DocTestCommand;

final class DocTestCommandTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = dirname(__DIR__, 2) . '/Fixtures';
    }

    #[Test]
    public function command_has_correct_name(): void
    {
        $command = new DocTestCommand();

        $this->assertSame('doctest', $command->getName());
    }

    #[Test]
    public function command_has_description(): void
    {
        $command = new DocTestCommand();

        $this->assertNotEmpty($command->getDescription());
    }

    #[Test]
    public function command_accepts_file_arguments(): void
    {
        $command = new DocTestCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('files'));
        $this->assertTrue($definition->getArgument('files')->isArray());
    }

    #[Test]
    public function command_has_filter_option(): void
    {
        $command = new DocTestCommand();

        $this->assertTrue($command->getDefinition()->hasOption('filter'));
    }

    #[Test]
    public function command_has_exclude_option(): void
    {
        $command = new DocTestCommand();

        $this->assertTrue($command->getDefinition()->hasOption('exclude'));
    }

    #[Test]
    public function command_has_dry_run_option(): void
    {
        $command = new DocTestCommand();

        $this->assertTrue($command->getDefinition()->hasOption('dry-run'));
    }

    #[Test]
    public function command_has_stop_on_failure_option(): void
    {
        $command = new DocTestCommand();

        $this->assertTrue($command->getDefinition()->hasOption('stop-on-failure'));
    }

    #[Test]
    public function command_has_config_option(): void
    {
        $command = new DocTestCommand();

        $this->assertTrue($command->getDefinition()->hasOption('config'));
    }

    #[Test]
    public function execute_returns_zero_for_passing_fixture(): void
    {
        $command = new DocTestCommand();
        $tester = new CommandTester($command);

        $tester->execute(['files' => [$this->fixturesDir . '/basic.md']]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    #[Test]
    public function execute_returns_one_for_failing_fixture(): void
    {
        $command = new DocTestCommand();
        $tester = new CommandTester($command);

        $tester->execute(['files' => [$this->fixturesDir . '/failing-output.md']]);

        $this->assertSame(1, $tester->getStatusCode());
    }

    #[Test]
    public function execute_returns_three_when_no_files_found(): void
    {
        $command = new DocTestCommand();
        $tester = new CommandTester($command);

        $tester->execute(['files' => ['/nonexistent/path.md']]);

        $this->assertSame(3, $tester->getStatusCode());
    }

    #[Test]
    public function execute_with_dry_run_returns_zero(): void
    {
        $command = new DocTestCommand();
        $tester = new CommandTester($command);

        $tester->execute(['files' => [$this->fixturesDir . '/basic.md'], '--dry-run' => true]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    #[Test]
    public function execute_output_contains_pass_for_passing_fixture(): void
    {
        $command = new DocTestCommand();
        $tester = new CommandTester($command);

        $tester->execute(['files' => [$this->fixturesDir . '/basic.md']]);

        $this->assertStringContainsString('✔', $tester->getDisplay());
    }

    #[Test]
    public function execute_with_stop_on_failure_stops_early(): void
    {
        $tempFile = sys_get_temp_dir() . '/doctest_cmd_stop_' . uniqid() . '.md';
        file_put_contents($tempFile, "```php\necho \"wrong\";\n// Output: right\n```\n\n```php\necho \"ok\";\n// Output: ok\n```\n");

        try {
            $command = new DocTestCommand();
            $tester = new CommandTester($command);

            $tester->execute(['files' => [$tempFile], '--stop-on-failure' => true]);

            $this->assertSame(1, $tester->getStatusCode());
            $this->assertSame(1, substr_count($tester->getDisplay(), '✖'));
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}
