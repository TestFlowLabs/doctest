<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TestFlowLabs\DocTest\Config\DocTestConfig;
use TestFlowLabs\DocTest\DocTest;

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
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to doctest.php config file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var array<string> $files */
        $files = $input->getArgument('files');

        $configPath = $input->getOption('config');
        $baseConfig = DocTestConfig::load(is_string($configPath) ? $configPath : null);

        $filter = $input->getOption('filter');
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
            reporterConsole: $baseConfig->reporterConsole,
            reporterJunit: $baseConfig->reporterJunit,
            reporterJson: $baseConfig->reporterJson,
        );

        $doctest = new DocTest($config, $output);

        return $doctest->run();
    }
}
