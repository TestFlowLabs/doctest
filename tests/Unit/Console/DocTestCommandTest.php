<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Console\DocTestCommand;
use Symfony\Component\Console\Tester\CommandTester;

beforeEach(function (): void {
    $this->fixturesDir = dirname(__DIR__, 2).'/Fixtures';
});
test('command has correct name', function (): void {
    $command = new DocTestCommand();

    expect($command->getName())->toBe('doctest');
});
test('command has description', function (): void {
    $command = new DocTestCommand();

    expect($command->getDescription())->not->toBeEmpty();
});
test('command accepts file arguments', function (): void {
    $command    = new DocTestCommand();
    $definition = $command->getDefinition();

    expect($definition->hasArgument('files'))->toBeTrue();
    expect($definition->getArgument('files')->isArray())->toBeTrue();
});
test('command has filter option', function (): void {
    $command = new DocTestCommand();

    expect($command->getDefinition()->hasOption('filter'))->toBeTrue();
});
test('command has exclude option', function (): void {
    $command = new DocTestCommand();

    expect($command->getDefinition()->hasOption('exclude'))->toBeTrue();
});
test('command has dry run option', function (): void {
    $command = new DocTestCommand();

    expect($command->getDefinition()->hasOption('dry-run'))->toBeTrue();
});
test('command has stop on failure option', function (): void {
    $command = new DocTestCommand();

    expect($command->getDefinition()->hasOption('stop-on-failure'))->toBeTrue();
});
test('command has config option', function (): void {
    $command = new DocTestCommand();

    expect($command->getDefinition()->hasOption('config'))->toBeTrue();
});
test('execute returns zero for passing fixture', function (): void {
    $command = new DocTestCommand();
    $tester  = new CommandTester($command);

    $tester->execute(['files' => [$this->fixturesDir.'/basic.md']]);

    expect($tester->getStatusCode())->toBe(0);
});
test('execute returns one for failing fixture', function (): void {
    $command = new DocTestCommand();
    $tester  = new CommandTester($command);

    $tester->execute(['files' => [$this->fixturesDir.'/failing-output.md']]);

    expect($tester->getStatusCode())->toBe(1);
});
test('execute returns three when no files found', function (): void {
    $command = new DocTestCommand();
    $tester  = new CommandTester($command);

    $tester->execute(['files' => ['/nonexistent/path.md']]);

    expect($tester->getStatusCode())->toBe(3);
});
test('execute with dry run returns zero', function (): void {
    $command = new DocTestCommand();
    $tester  = new CommandTester($command);

    $tester->execute(['files' => [$this->fixturesDir.'/basic.md'], '--dry-run' => true]);

    expect($tester->getStatusCode())->toBe(0);
});
test('execute output contains pass for passing fixture', function (): void {
    $command = new DocTestCommand();
    $tester  = new CommandTester($command);

    $tester->execute(['files' => [$this->fixturesDir.'/basic.md']]);

    $this->assertStringContainsString('✔', $tester->getDisplay());
});
test('command has parallel option that accepts optional value', function (): void {
    $command = new DocTestCommand();

    $definition = $command->getDefinition();

    expect($definition->hasOption('parallel'))->toBeTrue();
    expect($definition->getOption('parallel')->acceptValue())->toBeTrue();
    expect($definition->getOption('parallel')->isValueRequired())->toBeFalse();
});
test('parallel option without value auto-detects CPU cores', function (): void {
    $command = new DocTestCommand();
    $tester  = new CommandTester($command);

    $tester->execute(['files' => [$this->fixturesDir.'/basic.md'], '--parallel' => null]);

    expect($tester->getStatusCode())->toBe(0);
});
test('execute with block index runs only specified block', function (): void {
    $command = new DocTestCommand();
    $tester  = new CommandTester($command);

    $tester->execute(['files' => [$this->fixturesDir.'/basic.md:1']]);

    expect($tester->getStatusCode())->toBe(0);
    // Only 1 block should run
    expect(substr_count($tester->getDisplay(), '✔'))->toBe(1);
});
test('execute with out of range block index returns exit code 3', function (): void {
    $command = new DocTestCommand();
    $tester  = new CommandTester($command);

    $tester->execute(['files' => [$this->fixturesDir.'/basic.md:999']]);

    expect($tester->getStatusCode())->toBe(3);
});
test('execute with stop on failure stops early', function (): void {
    $tempFile = sys_get_temp_dir().'/doctest_cmd_stop_'.uniqid().'.md';
    file_put_contents($tempFile, "```php\necho \"wrong\";\n```\n<!-- doctest: right -->\n\n```php\necho \"ok\";\n```\n<!-- doctest: ok -->\n");

    try {
        $command = new DocTestCommand();
        $tester  = new CommandTester($command);

        $tester->execute(['files' => [$tempFile], '--stop-on-failure' => true]);

        expect($tester->getStatusCode())->toBe(1);
        expect(substr_count($tester->getDisplay(), '✖'))->toBe(1);
    } finally {
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
});
