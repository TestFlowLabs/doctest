<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest;

use TestFlowLabs\DocTest\Config\FileFinder;
use TestFlowLabs\DocTest\Executor\Executor;
use TestFlowLabs\DocTest\Config\DocTestConfig;
use TestFlowLabs\DocTest\Parser\MarkdownParser;
use TestFlowLabs\DocTest\Reporter\JsonReporter;
use TestFlowLabs\DocTest\Config\BootstrapResolver;
use TestFlowLabs\DocTest\Executor\ExecutionResult;
use TestFlowLabs\DocTest\Reporter\ConsoleReporter;
use TestFlowLabs\DocTest\Parser\CodeBlockExtractor;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class DocTest
{
    private FileFinder $fileFinder;
    private MarkdownParser $markdownParser;
    private CodeBlockExtractor $extractor;
    private Executor $executor;
    private ConsoleReporter $reporter;

    public function __construct(
        private DocTestConfig $config,
        ?OutputInterface $output = null,
    ) {
        $this->fileFinder     = new FileFinder();
        $this->markdownParser = new MarkdownParser();
        $this->extractor      = new CodeBlockExtractor();
        $bootstrapCode        = null;
        if ($config->bootstrap !== null) {
            $resolvedPath = realpath($config->bootstrap);
            if ($resolvedPath !== false) {
                $bootstrapCode = "require_once '".addslashes($resolvedPath)."';";
            }
        }

        $bootstrapResolver = null;
        $bootstrapsDir     = $config->bootstrapsDir;
        if (is_dir($bootstrapsDir)) {
            $bootstrapResolver = new BootstrapResolver($bootstrapsDir, $bootstrapCode);
        }

        $this->executor = new Executor($config->timeout, $config->memoryLimit, $config->normalizeWhitespace, $config->trimTrailing, $bootstrapCode, $bootstrapResolver, $config->parallel);

        if ($output !== null) {
            $this->reporter = new ConsoleReporter($output);
        } else {
            $this->reporter = new ConsoleReporter();
        }
    }

    public function run(): int
    {
        $startTime = microtime(true);
        $files     = $this->discoverFiles();

        if ($files === []) {
            return 3;
        }

        $allResults  = [];
        $hasFailure  = false;
        $totalBlocks = 0;

        // Count total blocks and find max line number for progress reporting
        $allBlocks     = [];
        $maxLineNumber = 0;
        foreach ($files as $file) {
            $blocks           = $this->extractBlocks($file);
            $allBlocks[$file] = $blocks;
            $totalBlocks += count($blocks);
            foreach ($blocks as $block) {
                $maxLineNumber = max($maxLineNumber, $block->startLine);
            }
        }

        $this->reporter->setTotalBlocks($totalBlocks);
        $this->reporter->setMaxLineNumber($maxLineNumber);

        foreach ($files as $file) {
            $this->reporter->reportFile($file);
            $blocks = $allBlocks[$file];

            if ($this->config->filter !== null) {
                $blocks = $this->applyFilter($blocks, $this->config->filter);
            }

            $stopEarly = false;

            $onResult = function (ExecutionResult $result) use (&$allResults, &$hasFailure, &$stopEarly): ?bool {
                $allResults[] = $result;
                $this->reporter->reportResult($result);

                if (!$result->passed && !$result->skipped) {
                    $hasFailure = true;

                    if ($this->config->stopOnFailure) {
                        $stopEarly = true;

                        return false;
                    }
                }

                return null;
            };

            if ($this->config->dryRun) {
                $results = $this->dryRunBlocks($blocks);

                foreach ($results as $result) {
                    $onResult($result);
                }
            } else {
                $this->executor->executeAll($blocks, onResult: $onResult);
            }

            if ($stopEarly) {
                $this->reporter->reportSummary($allResults, microtime(true) - $startTime);
                $this->writeReporterFiles($allResults);

                return 1;
            }
        }

        if ($allResults === []) {
            return 3;
        }

        $this->reporter->reportSummary($allResults, microtime(true) - $startTime);
        $this->writeReporterFiles($allResults);

        return $hasFailure ? 1 : 0;
    }

    /**
     * @return array<ExecutionResult>
     */
    public function testFile(string $filePath): array
    {
        $blocks = $this->extractBlocks($filePath);

        return $this->executor->executeAll($blocks);
    }

    /**
     * @return array<ExecutionResult>
     */
    public function testAll(): array
    {
        $files      = $this->discoverFiles();
        $allResults = [];

        foreach ($files as $file) {
            $allResults = array_merge($allResults, $this->testFile($file));
        }

        return $allResults;
    }

    /**
     * @return array<string>
     */
    private function discoverFiles(): array
    {
        return $this->fileFinder->find($this->config->paths, $this->config->exclude);
    }

    /**
     * @return array<\TestFlowLabs\DocTest\CodeBlock\CodeBlock>
     */
    private function extractBlocks(string $filePath): array
    {
        $markdown = file_get_contents($filePath);

        if ($markdown === false) {
            return [];
        }

        $document = $this->markdownParser->parse($markdown);

        return $this->extractor->extract($document, $filePath);
    }

    /**
     * @param  array<\TestFlowLabs\DocTest\CodeBlock\CodeBlock>  $blocks
     *
     * @return array<\TestFlowLabs\DocTest\CodeBlock\CodeBlock>
     */
    private function applyFilter(array $blocks, string $filter): array
    {
        return array_values(array_filter(
            $blocks,
            static fn ($block) => str_contains((string) $block->rawCode, $filter)
                || str_contains((string) $block->file, $filter),
        ));
    }

    /**
     * @param  array<\TestFlowLabs\DocTest\CodeBlock\CodeBlock>  $blocks
     *
     * @return array<ExecutionResult>
     */
    private function dryRunBlocks(array $blocks): array
    {
        $results = [];

        foreach ($blocks as $block) {
            $results[] = new ExecutionResult(
                passed: true,
                codeBlock: $block,
                skipped: true,
            );
        }

        return $results;
    }

    /**
     * @param  array<ExecutionResult>  $results
     */
    private function writeReporterFiles(array $results): void
    {
        if ($this->config->reporterJson !== null) {
            $reporter = new JsonReporter();
            $reporter->generateToFile($results, $this->config->reporterJson);
        }
    }
}
