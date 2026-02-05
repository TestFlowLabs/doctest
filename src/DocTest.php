<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest;

use TestFlowLabs\DocTest\Config\DocTestConfig;
use TestFlowLabs\DocTest\Config\FileFinder;
use TestFlowLabs\DocTest\Executor\ExecutionResult;
use TestFlowLabs\DocTest\Executor\Executor;
use TestFlowLabs\DocTest\Parser\CodeBlockExtractor;
use TestFlowLabs\DocTest\Parser\MarkdownParser;
use TestFlowLabs\DocTest\Reporter\ConsoleReporter;

final readonly class DocTest
{
    private FileFinder $fileFinder;

    private MarkdownParser $markdownParser;

    private CodeBlockExtractor $extractor;

    private Executor $executor;

    private ConsoleReporter $reporter;

    /**
     * @param resource|null $output
     */
    public function __construct(
        private DocTestConfig $config,
        $output = null,
    ) {
        $this->fileFinder = new FileFinder();
        $this->markdownParser = new MarkdownParser();
        $this->extractor = new CodeBlockExtractor();
        $this->executor = new Executor($config->timeout, $config->memoryLimit);
        $this->reporter = new ConsoleReporter($output, colors: $output === null);
    }

    public function run(): int
    {
        $startTime = microtime(true);
        $files = $this->discoverFiles();

        if ($files === []) {
            return 3;
        }

        $allResults = [];
        $hasFailure = false;

        foreach ($files as $file) {
            $this->reporter->reportFile($file);

            if ($this->config->dryRun) {
                $results = $this->dryRunFile($file);
            } else {
                $results = $this->testFile($file);
            }

            $allResults = array_merge($allResults, $results);

            foreach ($results as $result) {
                $this->reporter->reportResult($result);

                if (! $result->passed && ! $result->skipped) {
                    $hasFailure = true;

                    if ($this->config->stopOnFailure) {
                        $this->reporter->reportSummary($allResults, microtime(true) - $startTime);

                        return 1;
                    }
                }
            }
        }

        if ($allResults === []) {
            return 3;
        }

        $this->reporter->reportSummary($allResults, microtime(true) - $startTime);

        return $hasFailure ? 1 : 0;
    }

    /**
     * @return array<ExecutionResult>
     */
    public function testFile(string $filePath): array
    {
        $markdown = file_get_contents($filePath);

        if ($markdown === false) {
            return [];
        }

        $document = $this->markdownParser->parse($markdown);
        $blocks = $this->extractor->extract($document, $filePath);

        $results = [];

        foreach ($blocks as $block) {
            $results[] = $this->executor->execute($block);
        }

        return $results;
    }

    /**
     * @return array<ExecutionResult>
     */
    public function testAll(): array
    {
        $files = $this->discoverFiles();
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
     * @return array<ExecutionResult>
     */
    private function dryRunFile(string $filePath): array
    {
        $markdown = file_get_contents($filePath);

        if ($markdown === false) {
            return [];
        }

        $document = $this->markdownParser->parse($markdown);
        $blocks = $this->extractor->extract($document, $filePath);

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
}
