<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest;

use TestFlowLabs\DocTest\Config\FileFinder;
use TestFlowLabs\DocTest\Executor\Executor;
use TestFlowLabs\DocTest\Config\DocTestConfig;
use TestFlowLabs\DocTest\Parser\MarkdownParser;
use TestFlowLabs\DocTest\Reporter\JsonReporter;
use TestFlowLabs\DocTest\Updater\AssertionUpdate;
use TestFlowLabs\DocTest\Config\BootstrapResolver;
use TestFlowLabs\DocTest\Executor\ExecutionResult;
use TestFlowLabs\DocTest\Reporter\ConsoleReporter;
use TestFlowLabs\DocTest\Updater\MarkdownRewriter;
use TestFlowLabs\DocTest\Parser\CodeBlockExtractor;
use TestFlowLabs\DocTest\Comparison\WildcardMatcher;
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
        if ($this->config->update) {
            return $this->runUpdate();
        }

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
            $blocks = $this->extractBlocks($file);

            if (isset($this->config->blockIndices[$file])) {
                $index  = $this->config->blockIndices[$file] - 1;
                $blocks = ($index >= 0 && $index < count($blocks)) ? [$blocks[$index]] : [];
            }

            $allBlocks[$file] = $blocks;
            $totalBlocks += count($blocks);
            foreach ($blocks as $block) {
                $maxLineNumber = max($maxLineNumber, $block->startLine);
            }
        }

        $this->reporter->setTotalBlocks($totalBlocks);
        $this->reporter->setMaxLineNumber($maxLineNumber);
        $this->reporter->setParallelWorkers($this->config->parallel);

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

    private function runUpdate(): int
    {
        $startTime = microtime(true);
        $files     = $this->discoverFiles();

        if ($files === []) {
            return 3;
        }

        $rewriter        = new MarkdownRewriter();
        $wildcardMatcher = new WildcardMatcher();
        $totalUpdated    = 0;
        $filesUpdated    = 0;
        $allResults      = [];
        $hasRealFailure  = false;

        // Pre-compute blocks per file for accurate progress reporting
        $allBlocks     = [];
        $totalBlocks   = 0;
        $maxLineNumber = 0;
        foreach ($files as $file) {
            $blocks = $this->extractBlocks($file);

            if (isset($this->config->blockIndices[$file])) {
                $index  = $this->config->blockIndices[$file] - 1;
                $blocks = ($index >= 0 && $index < count($blocks)) ? [$blocks[$index]] : [];
            }

            if ($this->config->filter !== null) {
                $blocks = $this->applyFilter($blocks, $this->config->filter);
            }

            if ($blocks === []) {
                continue;
            }

            $allBlocks[$file] = $blocks;
            $totalBlocks += count($blocks);
            foreach ($blocks as $block) {
                $maxLineNumber = max($maxLineNumber, $block->startLine);
            }
        }

        $this->reporter->setTotalBlocks($totalBlocks);
        $this->reporter->setMaxLineNumber($maxLineNumber);
        $this->reporter->setParallelWorkers($this->config->parallel);

        foreach ($allBlocks as $file => $blocks) {
            $this->reporter->reportFile($file);

            $results        = $this->executor->executeAll($blocks);
            $updates        = [];
            $displayUpdates = [];
            $fileChanged    = false;

            foreach ($results as $result) {
                $allResults[] = $result;

                // Collect display output block updates (regardless of pass/fail)
                $displayBlock = $result->codeBlock->displayOutput;
                if ($displayBlock !== null && $result->actualOutput !== null) {
                    $displayUpdates[] = [
                        'block'  => $displayBlock,
                        'output' => $result->actualOutput,
                    ];
                }

                if ($result->passed || $result->skipped) {
                    $this->reporter->reportResult($result);

                    continue;
                }

                $fileUpdates = $this->collectUpdates($result, $wildcardMatcher);

                if ($fileUpdates !== []) {
                    $updates = array_merge($updates, $fileUpdates);
                    $this->reporter->reportUpdate($result, $fileUpdates);
                } else {
                    $hasRealFailure = true;
                    $this->reporter->reportResult($result);
                }
            }

            if ($updates !== []) {
                $count = $rewriter->rewrite($file, $updates);
                $totalUpdated += $count;
                if ($count > 0) {
                    $fileChanged = true;
                }
            }

            // Apply display output block updates (batched, single file read/write)
            if ($displayUpdates !== []) {
                $limitedDisplayUpdates = [];
                foreach ($displayUpdates as $du) {
                    /** @var \TestFlowLabs\DocTest\CodeBlock\DisplayOutputBlock $block */
                    $block                   = $du['block'];
                    $limitedDisplayUpdates[] = [
                        'block'  => $block,
                        'output' => $this->limitDisplayOutput((string) $du['output'], $block->lines, $block->tail),
                    ];
                }
                $rewriter->rewriteDisplayBlocks($file, $limitedDisplayUpdates);
                $totalUpdated += count($limitedDisplayUpdates);
                $fileChanged = true;
            }

            if ($fileChanged) {
                $filesUpdated++;
            }
        }

        $this->reporter->reportUpdateSummary($totalUpdated, $filesUpdated, microtime(true) - $startTime);

        if ($allResults === []) {
            return 3;
        }

        return $hasRealFailure ? 1 : 0;
    }

    /**
     * @return array<AssertionUpdate>
     */
    private function collectUpdates(ExecutionResult $result, WildcardMatcher $wildcardMatcher): array
    {
        $updates = [];

        foreach ($result->assertionDetails as $detail) {
            if ($detail->passed) {
                continue;
            }

            // Only updatable assertion types
            if ($detail->type === 'output') {
                // Skip wildcarded output assertions
                if ($wildcardMatcher->hasWildcards($detail->expected)) {
                    continue;
                }

                $updates[] = new AssertionUpdate(
                    markdownLine: $detail->line,
                    type: 'html_comment',
                    assertionType: 'output',
                    oldValue: $detail->expected,
                    newValue: $detail->actual,
                );
            } elseif ($detail->type === 'output_json') {
                $updates[] = new AssertionUpdate(
                    markdownLine: $detail->line,
                    type: 'html_comment',
                    assertionType: 'output_json',
                    oldValue: $detail->expected,
                    newValue: $detail->actual,
                );
            } elseif ($detail->type === 'result_comment') {
                // result_comment line is offset within code block
                // absolute markdown line = codeBlock.startLine + detail.line
                $absoluteLine = $result->codeBlock->startLine + $detail->line;

                $updates[] = new AssertionUpdate(
                    markdownLine: $absoluteLine,
                    type: 'result_comment',
                    assertionType: 'result_comment',
                    oldValue: $detail->expected,
                    newValue: $detail->actual,
                );
            }
            // output_contains, output_matches, expect → not updatable
        }

        return $updates;
    }

    private function limitDisplayOutput(string $output, ?int $lines, ?int $tail): string
    {
        if ($lines === null && $tail === null) {
            return $output;
        }

        $outputLines = explode("\n", $output);

        if ($lines !== null) {
            $outputLines = array_slice($outputLines, 0, $lines);
        } elseif ($tail !== null) {
            $outputLines = array_slice($outputLines, -$tail);
        }

        return implode("\n", $outputLines);
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
        $markdown = @file_get_contents($filePath);

        if ($markdown === false) {
            throw new \RuntimeException("Failed to read file: {$filePath}");
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
