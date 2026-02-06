<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Laravel;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use TestFlowLabs\DocTest\Laravel\DocTestCommand;

final class DocTestCommandTest extends TestCase
{
    #[Test]
    public function command_has_correct_signature(): void
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
    }

    #[Test]
    public function command_has_filter_option(): void
    {
        $command = new DocTestCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('filter'));
    }

    #[Test]
    public function command_has_dry_run_option(): void
    {
        $command = new DocTestCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('dry-run'));
    }

    #[Test]
    public function execute_returns_zero_for_passing_fixture(): void
    {
        $command = new DocTestCommand();
        $tester = new CommandTester($command);

        $fixtureFile = __DIR__ . '/../../Fixtures/basic.md';

        $tester->execute(['files' => [$fixtureFile]]);

        $this->assertSame(0, $tester->getStatusCode());
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

        $fixtureFile = __DIR__ . '/../../Fixtures/basic.md';

        $tester->execute(['files' => [$fixtureFile], '--dry-run' => true]);

        $this->assertSame(0, $tester->getStatusCode());
    }
}
