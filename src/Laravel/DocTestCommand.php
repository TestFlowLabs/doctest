<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Laravel;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

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
}
