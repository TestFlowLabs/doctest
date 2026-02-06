<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Laravel;

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
            ->addArgument('files', InputArgument::IS_ARRAY, 'Markdown files to test')
            ->addOption('filter', 'f', InputOption::VALUE_REQUIRED, 'Filter blocks by name pattern')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be executed without running');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var array<string> $files */
        $files = $input->getArgument('files');

        $baseConfig = DocTestConfig::load();
        $filter = $input->getOption('filter');

        $config = new DocTestConfig(
            paths: $files !== [] ? $files : $baseConfig->paths,
            exclude: $files !== [] ? [] : $baseConfig->exclude,
            timeout: $baseConfig->timeout,
            memoryLimit: $baseConfig->memoryLimit,
            stopOnFailure: $baseConfig->stopOnFailure,
            dryRun: $input->getOption('dry-run') === true || $baseConfig->dryRun,
            filter: is_string($filter) ? $filter : $baseConfig->filter,
            verbosity: $baseConfig->verbosity,
        );

        $doctest = new DocTest($config, $output);

        return $doctest->run();
    }
}
