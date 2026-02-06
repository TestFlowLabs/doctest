<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Console;

use TestFlowLabs\DocTest\DocTest;
use Symfony\Component\Console\Command\Command;
use TestFlowLabs\DocTest\Config\DocTestConfig;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class DocTestCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('doctest')
            ->setDescription('Run documentation tests extracted from markdown files')
            ->addArgument('files', InputArgument::IS_ARRAY, 'Markdown files or directories to test')
            ->addOption('filter', 'f', InputOption::VALUE_REQUIRED, 'Filter blocks by content or file name')
            ->addOption('exclude', null, InputOption::VALUE_REQUIRED, 'Exclude files matching pattern')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Parse and show blocks without executing')
            ->addOption('stop-on-failure', null, InputOption::VALUE_NONE, 'Stop on first failure')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to doctest.php config file')
            ->setHelp(<<<'HELP'
                Extracts PHP code blocks from markdown files and executes them,
                verifying output and assertions match expected values.

                <comment>Verbosity levels:</comment>
                  (default)  Block-level pass/fail only
                  <info>-v</info>         Show per-assertion details under each block
                  <info>-vv</info>        Also show source code on failure

                <comment>Assertion types (HTML comments after code block):</comment>
                  \<!-- doctest: Hello --\>            Exact output match
                  \<!-- doctest-contains: He --\>      Partial output match
                  \<!-- doctest-matches: /H/ --\>      Regex output match
                  \<!-- doctest-json: {"k":"v"} --\>   JSON structure match
                  \<!-- doctest-expect: $x === 42 --\> Expression must be truthy
                  <info>$x = 42; // => 42</info>               Return value match

                <comment>Attributes (in code fence info string):</comment>
                  <info>```php ignore</info>                        Skip this block
                  <info>```php no_run</info>                        Syntax check only
                  <info>```php throws(RuntimeException)</info>      Expect exception
                  <info>```php parse_error</info>                   Expect parse error
                  <info>```php group="name"</info>                  Group blocks sharing state
                  <info>```php setup group="name"</info>            Setup code for a group
                  <info>```php teardown group="name"</info>         Teardown code for a group

                <comment>Examples:</comment>
                  <info>doctest</info>
                    Run tests from default paths (docs/ and README.md)

                  <info>doctest README.md docs/api.md</info>
                    Test specific files

                  <info>doctest docs/</info>
                    Test all markdown files in a directory

                  <info>doctest -v</info>
                    Show per-assertion details

                  <info>doctest -vv</info>
                    Show per-assertion details and source code on failure

                  <info>doctest --filter "array_map"</info>
                    Only run blocks containing "array_map"

                  <info>doctest --dry-run</info>
                    Parse and list blocks without executing

                  <info>doctest --stop-on-failure</info>
                    Stop at the first failing block

                  <info>doctest --exclude vendor</info>
                    Skip files matching "vendor"

                  <info>doctest -c custom-doctest.php</info>
                    Use a custom config file
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var array<string> $files */
        $files = $input->getArgument('files');

        $configPath = $input->getOption('config');
        $baseConfig = DocTestConfig::load(is_string($configPath) ? $configPath : null);

        $filter  = $input->getOption('filter');
        $exclude = $input->getOption('exclude');

        $config = new DocTestConfig(
            paths: $files !== [] ? $files : $baseConfig->paths,
            exclude: is_string($exclude) ? [$exclude] : $baseConfig->exclude,
            timeout: $baseConfig->timeout,
            memoryLimit: $baseConfig->memoryLimit,
            stopOnFailure: $input->getOption('stop-on-failure') === true || $baseConfig->stopOnFailure,
            dryRun: $input->getOption('dry-run') === true || $baseConfig->dryRun,
            filter: is_string($filter) ? $filter : $baseConfig->filter,
            verbosity: $baseConfig->verbosity,
            bootstrap: $baseConfig->bootstrap,
            reporterConsole: $baseConfig->reporterConsole,
            reporterJson: $baseConfig->reporterJson,
        );

        $doctest = new DocTest($config, $output);

        return $doctest->run();
    }
}
