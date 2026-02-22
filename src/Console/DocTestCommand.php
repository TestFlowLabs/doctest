<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Console;

use Composer\InstalledVersions;
use TestFlowLabs\DocTest\DocTest;
use Symfony\Component\Console\Command\Command;
use TestFlowLabs\DocTest\Config\DocTestConfig;
use Symfony\Component\Console\Input\InputOption;
use TestFlowLabs\DocTest\System\CpuCoreDetector;
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
            ->addOption('parallel', 'p', InputOption::VALUE_OPTIONAL, 'Number of parallel workers (auto-detects CPU cores when no value given)')
            ->addOption('update', 'u', InputOption::VALUE_NONE, 'Update outdated assertion values with actual output')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to doctest.php config file')
            ->setHelp(
                '<comment>DocTest</comment> v'.(InstalledVersions::getPrettyVersion('testflowlabs/doctest') ?? 'dev')."\n\n".<<<'HELP'
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
                  <info>$x = 42; // => dd()</info>             Debug dump (shows value, always passes)

                <comment>Attributes (in code fence info string):</comment>
                  <info>```php ignore</info>                        Skip this block
                  <info>```php no_run</info>                        Syntax check only
                  <info>```php throws(RuntimeException)</info>      Expect exception
                  <info>```php parse_error</info>                   Expect parse error
                  <info>```php group="name"</info>                  Group blocks sharing state
                  <info>```php setup group="name"</info>            Setup code for a group
                  <info>```php teardown group="name"</info>         Teardown code for a group
                  <info>```php bootstrap="name"</info>              Use named bootstrap profile
                  <info>```php bootstrap="a,b"</info>               Compose multiple profiles

                <comment>Attributes (HTML comment — preserves editor syntax highlighting):</comment>
                  \<!-- doctest-attr: ignore --\>            Same as info string, but in a comment
                  \<!-- doctest-attr: group="name" --\>      Group via comment
                  \<!-- doctest-attr: throws(Ex) --\>        Expect exception via comment
                  \<!-- doctest-attr: bootstrap="name" --\>  Bootstrap via comment
                  Place the comment on the line before the code block.
                  Both syntaxes can coexist (but not for the same attribute).

                <comment>Bootstrap profiles (.doctest/ directory):</comment>
                  Files in <info>.doctest/</info> are auto-discovered as profiles.
                  Profile name = filename without .php extension.
                  Order: global bootstrap → profiles (left to right) → setup → code

                <comment>Shiki transformations (auto-stripped):</comment>
                  <info>// [!code --]</info>                      Line removed (diff removal)
                  <info>// [!code ++]</info>                      Marker stripped (diff addition)
                  <info>// [!code hide]</info>                    Marker stripped (single-line hide)
                  <info>// [!code hide:start]</info>              Delimiter line removed (block hide start)
                  <info>// [!code hide:end]</info>                Delimiter line removed (block hide end)
                  <info>// [!code highlight]</info>               Marker stripped
                  <info>// [!code focus]</info>                   Marker stripped
                  <info>{1,4-6}</info> (in info string)           Line highlight notation stripped

                <comment>Examples:</comment>
                  <info>doctest</info>
                    Run tests from default paths (docs/ and README.md)

                  <info>doctest README.md docs/api.md</info>
                    Test specific files

                  <info>doctest README.md:3</info>
                    Test only the 3rd PHP block in README.md

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

                  <info>doctest --parallel</info>
                    Run in parallel (auto-detect CPU cores)

                  <info>doctest --parallel 4</info>
                    Run with 4 parallel workers

                  <info>doctest --update</info>
                    Update stale assertion values with actual output

                  <info>doctest docs/api.md -u</info>
                    Update assertions in a specific file
                HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var array<string> $files */
        $files = $input->getArgument('files');

        $blockIndices = [];
        $cleanFiles   = [];
        foreach ($files as $file) {
            if (preg_match('/^(.+):(\d+)$/', $file, $matches) === 1 && !is_dir($file)) {
                $cleanFiles[]              = $matches[1];
                $blockIndices[$matches[1]] = (int) $matches[2];
            } else {
                $cleanFiles[] = $file;
            }
        }
        $files = $cleanFiles;

        $isUpdate = $input->getOption('update') === true;
        $isDryRun = $input->getOption('dry-run') === true;

        if ($isUpdate && $isDryRun) {
            $output->writeln('<error>The --update and --dry-run options are mutually exclusive.</error>');

            return 1;
        }

        $configPath = $input->getOption('config');
        $baseConfig = DocTestConfig::load(is_string($configPath) ? $configPath : null);

        $filter   = $input->getOption('filter');
        $exclude  = $input->getOption('exclude');
        $parallel = $input->getOption('parallel');

        if (is_numeric($parallel)) {
            $parallelValue = max(1, (int) $parallel);
        } elseif ($parallel === null && $input->hasParameterOption(['--parallel', '-p'])) {
            $parallelValue = CpuCoreDetector::detect();
        } else {
            $parallelValue = $baseConfig->parallel;
        }

        $config = new DocTestConfig(
            paths: $files !== [] ? $files : $baseConfig->paths,
            exclude: is_string($exclude) ? [$exclude] : $baseConfig->exclude,
            timeout: $baseConfig->timeout,
            memoryLimit: $baseConfig->memoryLimit,
            stopOnFailure: $input->getOption('stop-on-failure') === true || $baseConfig->stopOnFailure,
            dryRun: $isDryRun || $baseConfig->dryRun,
            filter: is_string($filter) ? $filter : $baseConfig->filter,
            verbosity: $baseConfig->verbosity,
            bootstrap: $baseConfig->bootstrap,
            parallel: $parallelValue,
            reporterConsole: $baseConfig->reporterConsole,
            reporterJson: $baseConfig->reporterJson,
            blockIndices: $blockIndices,
            update: $isUpdate || $baseConfig->update,
        );

        $doctest = new DocTest($config, $output);

        return $doctest->run();
    }
}
