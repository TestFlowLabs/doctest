<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Laravel;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
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
}
