<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Executor;

use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\Comparison\DiffGenerator;
use TestFlowLabs\DocTest\Config\BootstrapResolver;
use TestFlowLabs\DocTest\Comparison\OutputComparator;

final readonly class Executor
{
    private CodeGenerator $codeGenerator;
    private ProcessRunner $processRunner;
    private OutputComparator $comparator;
    private DiffGenerator $diffGenerator;
    private \TestFlowLabs\DocTest\Assertion\AssertionParser $assertionParser;
    private ?ParallelExecutor $parallelExecutor;

    public function __construct(
        private int $timeout = 5,
        private string $memoryLimit = '128M',
        bool $normalizeWhitespace = true,
        bool $trimTrailing = true,
        ?string $bootstrapCode = null,
        private ?BootstrapResolver $bootstrapResolver = null,
        private int $parallel = 1,
    ) {
        $this->codeGenerator   = new CodeGenerator($bootstrapCode);
        $this->processRunner   = new ProcessRunner($timeout, $memoryLimit);
        $this->comparator      = new OutputComparator($normalizeWhitespace, $trimTrailing);
        $this->diffGenerator   = new DiffGenerator();
        $this->assertionParser = new \TestFlowLabs\DocTest\Assertion\AssertionParser();

        if ($this->parallel > 1) {
            $workerPool             = new WorkerPool($this->parallel, $this->timeout, $this->memoryLimit);
            $this->parallelExecutor = new ParallelExecutor($this->codeGenerator, $workerPool);
        } else {
            $this->parallelExecutor = null;
        }
    }

    /**
     * @param  array<CodeBlock>  $blocks
     * @param  ?\Closure(ExecutionResult): ?bool  $onResult  Return false to stop execution early
     *
     * @return array<ExecutionResult>
     */
    public function executeAll(array $blocks, ?\Closure $onResult = null): array
    {
        [$setup, $teardown, $normalBlocks, $groupedBlocks] = $this->collectSetupTeardownAndGroups($blocks);

        if ($this->parallelExecutor !== null) {
            return $this->executeAllParallel($normalBlocks, $groupedBlocks, $setup, $teardown, $onResult);
        }

        return $this->executeAllSequential($normalBlocks, $groupedBlocks, $setup, $teardown, $onResult);
    }

    /**
     * @param  array<CodeBlock>  $normalBlocks
     * @param  array<string, array<CodeBlock>>  $groupedBlocks
     * @param  ?\Closure(ExecutionResult): ?bool  $onResult
     *
     * @return array<ExecutionResult>
     */
    private function executeAllSequential(array $normalBlocks, array $groupedBlocks, ?string $setup, ?string $teardown, ?\Closure $onResult): array
    {
        $results = [];
        $stopped = false;

        foreach ($normalBlocks as $block) {
            if ($block->attributes->isIgnore()) {
                $result = new ExecutionResult(passed: true, codeBlock: $block, skipped: true);
            } elseif ($block->attributes->isNoRun()) {
                $result = $this->syntaxCheck($block);
            } elseif ($block->attributes->isParseError()) {
                $result = $this->executeParseError($block);
            } elseif ($block->attributes->isThrows()) {
                $result = $this->executeThrows($block);
            } else {
                $result = $this->executeNormal($block, $setup, $teardown);
            }

            $results[] = $result;

            if ($onResult !== null && $onResult($result) === false) {
                $stopped = true;

                break;
            }
        }

        if (!$stopped) {
            foreach ($groupedBlocks as $groupBlocks) {
                $groupResults = $this->executeGroupBlocks($groupBlocks, $setup, $teardown);

                foreach ($groupResults as $result) {
                    $results[] = $result;

                    if ($onResult !== null && $onResult($result) === false) {
                        $stopped = true;

                        break 2;
                    }
                }
            }
        }

        return $results;
    }

    /**
     * @param  array<CodeBlock>  $normalBlocks
     * @param  array<string, array<CodeBlock>>  $groupedBlocks
     * @param  ?\Closure(ExecutionResult): ?bool  $onResult
     *
     * @return array<ExecutionResult>
     */
    private function executeAllParallel(array $normalBlocks, array $groupedBlocks, ?string $setup, ?string $teardown, ?\Closure $onResult): array
    {
        $results = [];
        $stopped = false;

        // Separate blocks that need process execution from skip/syntax-only blocks
        $processableBlocks   = [];
        $processableIndexMap = [];

        foreach ($normalBlocks as $i => $block) {
            if ($block->attributes->isIgnore()) {
                $result    = new ExecutionResult(passed: true, codeBlock: $block, skipped: true);
                $results[] = $result;

                if ($onResult !== null && $onResult($result) === false) {
                    return $results;
                }
            } elseif ($block->attributes->isNoRun()) {
                $result    = $this->syntaxCheck($block);
                $results[] = $result;

                if ($onResult !== null && $onResult($result) === false) {
                    return $results;
                }
            } else {
                $processableIndexMap[] = $i;
                $processableBlocks[]   = $block;
            }
        }

        // Run processable blocks in parallel
        if ($processableBlocks !== [] && $this->parallelExecutor !== null) {
            // Resolve bootstrap per block
            $blockBootstrapCodes = [];
            foreach ($processableBlocks as $index => $block) {
                $blockBootstrapCodes[$index] = $this->resolveBlockBootstrap($block);
            }

            $processResults = $this->parallelExecutor->execute($processableBlocks, $setup, $teardown, $blockBootstrapCodes);

            foreach ($processableBlocks as $index => $block) {
                if (!isset($processResults[$index])) {
                    continue;
                }

                $processResult = $processResults[$index];
                $result        = $this->evaluateProcessResult($block, $processResult);
                $results[]     = $result;

                if ($onResult !== null && $onResult($result) === false) {
                    $stopped = true;

                    break;
                }
            }
        }

        // Groups still run sequentially
        if (!$stopped) {
            foreach ($groupedBlocks as $groupBlocks) {
                $groupResults = $this->executeGroupBlocks($groupBlocks, $setup, $teardown);

                foreach ($groupResults as $result) {
                    $results[] = $result;

                    if ($onResult !== null && $onResult($result) === false) {
                        $stopped = true;

                        break 2;
                    }
                }
            }
        }

        return $results;
    }

    private function evaluateProcessResult(CodeBlock $block, ProcessResult $processResult): ExecutionResult
    {
        if ($block->attributes->isParseError()) {
            $passed = $processResult->exitCode !== 0;

            return new ExecutionResult(
                passed: $passed,
                codeBlock: $block,
                error: $passed ? null : 'Expected parse error but code ran successfully',
                duration: $processResult->duration,
            );
        }

        if ($block->attributes->isThrows()) {
            return $this->evaluateThrowsResult($block, $processResult);
        }

        return $this->evaluateNormalResult($block, $processResult);
    }

    private function evaluateThrowsResult(CodeBlock $block, ProcessResult $processResult): ExecutionResult
    {
        /** @var array{thrown?: bool, class?: string, message?: string} $data */
        $data = json_decode($processResult->stderr, true) ?? [];

        if (!isset($data['thrown']) || $data['thrown'] !== true) {
            return new ExecutionResult(
                passed: false,
                codeBlock: $block,
                error: 'Expected exception '.($block->attributes->throwsClass ?? 'Throwable').' but none was thrown',
                duration: $processResult->duration,
            );
        }

        if ($block->attributes->throwsClass !== null && isset($data['class'])) {
            $normalizedActual   = ltrim($data['class'], '\\');
            $normalizedExpected = ltrim($block->attributes->throwsClass, '\\');

            if ($normalizedActual !== $normalizedExpected) {
                return new ExecutionResult(
                    passed: false,
                    codeBlock: $block,
                    error: "Expected exception {$block->attributes->throwsClass} but got {$data['class']}",
                    duration: $processResult->duration,
                );
            }
        }

        if ($block->attributes->throwsMessage !== null && isset($data['message'])) {
            if (!str_contains($data['message'], $block->attributes->throwsMessage)) {
                return new ExecutionResult(
                    passed: false,
                    codeBlock: $block,
                    error: "Expected message containing \"{$block->attributes->throwsMessage}\" but got \"{$data['message']}\"",
                    duration: $processResult->duration,
                );
            }
        }

        return new ExecutionResult(
            passed: true,
            codeBlock: $block,
            duration: $processResult->duration,
        );
    }

    private function evaluateNormalResult(CodeBlock $block, ProcessResult $processResult): ExecutionResult
    {
        $decoded = json_decode($processResult->stderr, true);

        if ($processResult->exitCode !== 0 && !is_array($decoded)) {
            $errorMessage = $processResult->stderr !== ''
                ? 'Process failed (exit code '.$processResult->exitCode.'): '.$processResult->stderr
                : 'Process failed with exit code '.$processResult->exitCode;

            return new ExecutionResult(
                passed: false,
                codeBlock: $block,
                error: $errorMessage,
                duration: $processResult->duration,
            );
        }

        /** @var array<array{type: string, expected?: string, actual?: string, expression?: string, passed?: bool, line?: int, value?: string}> $results */
        $results = is_array($decoded) ? $decoded : [];

        if (!is_array($decoded) && $processResult->stdout !== '') {
            $stdout = trim($processResult->stdout);

            if ($stdout !== '') {
                return new ExecutionResult(
                    passed: false,
                    codeBlock: $block,
                    error: $stdout,
                    duration: $processResult->duration,
                );
            }
        }

        return $this->evaluateResults($block, $results, $processResult);
    }

    /**
     * @param  array<CodeBlock>  $blocks
     *
     * @return array<ExecutionResult>
     */
    public function executeGroup(array $blocks): array
    {
        return $this->executeGroupBlocks($blocks);
    }

    public function execute(CodeBlock $block): ExecutionResult
    {
        if ($block->attributes->isIgnore()) {
            return new ExecutionResult(passed: true, codeBlock: $block, skipped: true);
        }

        if ($block->attributes->isNoRun()) {
            return $this->syntaxCheck($block);
        }

        if ($block->attributes->isParseError()) {
            return $this->executeParseError($block);
        }

        if ($block->attributes->isThrows()) {
            return $this->executeThrows($block);
        }

        return $this->executeNormal($block);
    }

    private function syntaxCheck(CodeBlock $block): ExecutionResult
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'doctest_').'.php';
        file_put_contents($tempFile, "<?php\n".$block->executableCode);

        $output   = [];
        $exitCode = 0;
        exec(PHP_BINARY.' -l '.escapeshellarg($tempFile).' 2>&1', $output, $exitCode);
        unlink($tempFile);

        if ($exitCode === 0) {
            return new ExecutionResult(passed: true, codeBlock: $block);
        }

        return new ExecutionResult(
            passed: false,
            codeBlock: $block,
            error: 'Syntax error: '.implode("\n", $output),
        );
    }

    private function executeParseError(CodeBlock $block): ExecutionResult
    {
        $filePath      = $this->codeGenerator->generate($block, blockBootstrapCode: $this->resolveBlockBootstrap($block));
        $processResult = $this->processRunner->run($filePath);
        $this->cleanup($filePath);

        // parse_error blocks should fail syntax check — a non-zero exit code is expected
        $passed = $processResult->exitCode !== 0;

        return new ExecutionResult(
            passed: $passed,
            codeBlock: $block,
            error: $passed ? null : 'Expected parse error but code ran successfully',
            duration: $processResult->duration,
        );
    }

    private function executeThrows(CodeBlock $block): ExecutionResult
    {
        $filePath = $this->codeGenerator->generate($block, blockBootstrapCode: $this->resolveBlockBootstrap($block));

        $syntaxError = $this->preSyntaxCheck($filePath, $block);
        if ($syntaxError !== null) {
            return $syntaxError;
        }

        $processResult = $this->processRunner->run($filePath);
        $this->cleanup($filePath);

        return $this->evaluateThrowsResult($block, $processResult);
    }

    private function executeNormal(CodeBlock $block, ?string $setup = null, ?string $teardown = null): ExecutionResult
    {
        $filePath = $this->codeGenerator->generate($block, setup: $setup, teardown: $teardown, blockBootstrapCode: $this->resolveBlockBootstrap($block));

        $syntaxError = $this->preSyntaxCheck($filePath, $block);
        if ($syntaxError !== null) {
            return $syntaxError;
        }

        $processResult = $this->processRunner->run($filePath);
        $this->cleanup($filePath);

        return $this->evaluateNormalResult($block, $processResult);
    }

    /**
     * @param  array<array{type: string, expected?: string, actual?: string, expression?: string, passed?: bool, line?: int, value?: string}>  $results
     */
    private function evaluateResults(CodeBlock $block, array $results, ProcessResult $processResult): ExecutionResult
    {
        $capturedOutput   = [];
        $assertionDetails = [];
        $debugOutputs     = [];

        // Track first failure details for reporting
        $hasFailed       = false;
        $failureActual   = null;
        $failureExpected = null;
        $failureDiff     = null;
        $failureError    = null;

        foreach ($results as $result) {
            if ($result['type'] === 'debug') {
                $debugOutputs[] = new DebugOutput(
                    expression: $result['expression'] ?? '',
                    value: $result['value'] ?? '',
                    line: $result['line'] ?? 0,
                );

                continue;
            }
            if ($result['type'] === 'output') {
                $expected         = $result['expected'] ?? '';
                $actual           = $result['actual'] ?? '';
                $capturedOutput[] = $actual;
                $comparison       = $this->comparator->compare($expected, $actual);

                $assertionDetails[] = new \TestFlowLabs\DocTest\Assertion\AssertionResultDetail(
                    type: 'output',
                    passed: $comparison->passed,
                    expected: $expected,
                    actual: $actual,
                    line: $result['line'] ?? 0,
                );

                if (!$comparison->passed && !$hasFailed) {
                    $hasFailed       = true;
                    $failureActual   = $actual;
                    $failureExpected = $expected;
                    $failureDiff     = $this->diffGenerator->generate($comparison->normalizedExpected, $comparison->normalizedActual);
                }
            }

            if ($result['type'] === 'output_contains') {
                $expected         = $result['expected'] ?? '';
                $actual           = $result['actual'] ?? '';
                $capturedOutput[] = $actual;
                $passed           = str_contains($actual, $expected);

                $assertionDetails[] = new \TestFlowLabs\DocTest\Assertion\AssertionResultDetail(
                    type: 'output_contains',
                    passed: $passed,
                    expected: $expected,
                    actual: $actual,
                    line: $result['line'] ?? 0,
                );

                if (!$passed && !$hasFailed) {
                    $hasFailed       = true;
                    $failureActual   = $actual;
                    $failureExpected = $expected;
                    $failureError    = "Output does not contain: {$expected}";
                }
            }

            if ($result['type'] === 'output_matches') {
                $pattern          = $result['expected'] ?? '';
                $actual           = $result['actual'] ?? '';
                $capturedOutput[] = $actual;

                set_error_handler(static fn () => true);

                try {
                    $matchResult = preg_match($pattern, $actual);
                } finally {
                    restore_error_handler();
                }

                if ($matchResult === false) {
                    $assertionDetails[] = new \TestFlowLabs\DocTest\Assertion\AssertionResultDetail(
                        type: 'output_matches',
                        passed: false,
                        expected: $pattern,
                        actual: $actual,
                        line: $result['line'] ?? 0,
                    );

                    if (!$hasFailed) {
                        $hasFailed    = true;
                        $failureError = "Invalid regex pattern: {$pattern}";
                    }

                    continue;
                }

                $passed = $matchResult === 1;

                $assertionDetails[] = new \TestFlowLabs\DocTest\Assertion\AssertionResultDetail(
                    type: 'output_matches',
                    passed: $passed,
                    expected: $pattern,
                    actual: $actual,
                    line: $result['line'] ?? 0,
                );

                if (!$passed && !$hasFailed) {
                    $hasFailed       = true;
                    $failureActual   = $actual;
                    $failureExpected = $pattern;
                    $failureError    = "Output does not match pattern: {$pattern}";
                }
            }

            if ($result['type'] === 'output_json') {
                $expectedJson     = $result['expected'] ?? '';
                $actual           = $result['actual'] ?? '';
                $capturedOutput[] = $actual;

                $jsonResult = $this->comparator->compareJson($expectedJson, $actual);

                $assertionDetails[] = new \TestFlowLabs\DocTest\Assertion\AssertionResultDetail(
                    type: 'output_json',
                    passed: $jsonResult->passed,
                    expected: $expectedJson,
                    actual: $actual,
                    line: $result['line'] ?? 0,
                );

                if (!$jsonResult->passed && !$hasFailed) {
                    $hasFailed       = true;
                    $failureActual   = $actual;
                    $failureExpected = $expectedJson;
                    $failureError    = match (true) {
                        str_contains($jsonResult->normalizedExpected, 'Expected JSON is invalid')    => $jsonResult->normalizedExpected,
                        str_contains($jsonResult->normalizedActual, 'Actual JSON output is invalid') => $jsonResult->normalizedActual,
                        default                                                                      => 'JSON output does not match expected structure',
                    };
                }
            }

            if ($result['type'] === 'expect') {
                $passed     = (bool) ($result['passed'] ?? false);
                $expression = $result['expression'] ?? '';

                $assertionDetails[] = new \TestFlowLabs\DocTest\Assertion\AssertionResultDetail(
                    type: 'expect',
                    passed: $passed,
                    expected: $expression,
                    actual: $passed ? 'true' : 'false',
                    line: $result['line'] ?? 0,
                    expression: $expression,
                );

                if (!$passed && !$hasFailed) {
                    $hasFailed    = true;
                    $failureError = 'Expect assertion failed: '.$expression;
                }
            }

            if ($result['type'] === 'result_comment') {
                $expected = $result['expected'] ?? '';
                $actual   = $result['actual'] ?? '';
                $passed   = $expected === $actual;

                $assertionDetails[] = new \TestFlowLabs\DocTest\Assertion\AssertionResultDetail(
                    type: 'result_comment',
                    passed: $passed,
                    expected: $expected,
                    actual: $actual,
                    line: $result['line'] ?? 0,
                    expression: $result['expression'] ?? null,
                );

                if (!$passed && !$hasFailed) {
                    $hasFailed    = true;
                    $failureError = "result_comment assertion failed: expected {$expected} but got {$actual}";
                }
            }
        }

        if ($hasFailed) {
            return new ExecutionResult(
                passed: false,
                codeBlock: $block,
                actualOutput: $failureActual,
                expectedOutput: $failureExpected,
                diff: $failureDiff,
                error: $failureError,
                duration: $processResult->duration,
                assertionDetails: $assertionDetails,
                debugOutputs: $debugOutputs,
            );
        }

        $actualOutput = $capturedOutput !== [] ? implode('', $capturedOutput) : ($processResult->stdout !== '' ? $processResult->stdout : null);

        return new ExecutionResult(
            passed: true,
            codeBlock: $block,
            actualOutput: $actualOutput,
            duration: $processResult->duration,
            assertionDetails: $assertionDetails,
            debugOutputs: $debugOutputs,
        );
    }

    /**
     * @param  array<CodeBlock>  $blocks
     *
     * @return array<ExecutionResult>
     */
    private function executeGroupBlocks(array $blocks, ?string $setup = null, ?string $teardown = null): array
    {
        if ($this->bootstrapResolver !== null && count($blocks) > 1) {
            $firstBootstraps = $blocks[0]->attributes->bootstraps;
            foreach ($blocks as $block) {
                if ($block->attributes->bootstraps !== $firstBootstraps) {
                    $group = $blocks[0]->attributes->group ?? 'unknown';

                    throw new \RuntimeException(
                        "All blocks in group \"{$group}\" must have identical bootstrap profiles."
                    );
                }
            }
        }

        $blockBootstrap = $blocks !== [] ? $this->resolveBlockBootstrap($blocks[0]) : null;
        $filePath       = $this->codeGenerator->generateGroup($blocks, setup: $setup, teardown: $teardown, blockBootstrapCode: $blockBootstrap);

        $syntaxError = $this->preSyntaxCheck($filePath, $blocks[0] ?? null);
        if ($syntaxError !== null) {
            return array_map(
                fn (CodeBlock $block) => new ExecutionResult(
                    passed: false,
                    codeBlock: $block,
                    error: $syntaxError->error,
                ),
                $blocks,
            );
        }

        $processResult = $this->processRunner->run($filePath);
        $this->cleanup($filePath);

        if ($processResult->exitCode !== 0) {
            $decoded = json_decode($processResult->stderr, true);

            if (!is_array($decoded)) {
                $errorMessage = $processResult->stderr !== ''
                    ? 'Process failed (exit code '.$processResult->exitCode.'): '.$processResult->stderr
                    : 'Process failed with exit code '.$processResult->exitCode;

                return array_map(
                    fn (CodeBlock $block) => new ExecutionResult(
                        passed: false,
                        codeBlock: $block,
                        error: $errorMessage,
                        duration: $processResult->duration,
                    ),
                    $blocks,
                );
            }
        }

        $decoded = json_decode($processResult->stderr, true);

        /** @var array<array{type: string, expected?: string, actual?: string, expression?: string, passed?: bool, line?: int, value?: string}> $allResults */
        $allResults = is_array($decoded) ? $decoded : [];

        return $this->mapResultsToBlocks($blocks, $allResults, $processResult);
    }

    /**
     * @param  array<CodeBlock>  $blocks
     * @param  array<array{type: string, expected?: string, actual?: string, expression?: string, passed?: bool, line?: int, value?: string}>  $allResults
     *
     * @return array<ExecutionResult>
     */
    private function mapResultsToBlocks(array $blocks, array $allResults, ProcessResult $processResult): array
    {
        // Count expected assertions per block to partition results
        $assertionCounts = [];
        foreach ($blocks as $block) {
            $parsed            = $this->assertionParser->parse($block->rawCode);
            $count             = count($block->assertions) + count($parsed->resultComments) + count($parsed->debugMarkers);
            $assertionCounts[] = $count;
        }

        $results     = [];
        $resultIndex = 0;

        foreach ($blocks as $blockIndex => $block) {
            $blockAssertionCount = $assertionCounts[$blockIndex];

            if ($blockAssertionCount === 0) {
                $results[] = new ExecutionResult(
                    passed: true,
                    codeBlock: $block,
                    duration: $processResult->duration,
                );

                continue;
            }

            $blockResults = array_slice($allResults, $resultIndex, $blockAssertionCount);
            $resultIndex += $blockAssertionCount;

            $results[] = $this->evaluateResults($block, $blockResults, $processResult);
        }

        return $results;
    }

    /**
     * @param  array<CodeBlock>  $blocks
     *
     * @return array{?string, ?string, array<CodeBlock>, array<string, array<CodeBlock>>}
     */
    private function collectSetupTeardownAndGroups(array $blocks): array
    {
        $setupCode    = [];
        $teardownCode = [];
        $normalBlocks = [];
        /** @var array<string, array<CodeBlock>> $groupedBlocks */
        $groupedBlocks = [];

        foreach ($blocks as $block) {
            if ($block->attributes->isSetup()) {
                $setupCode[] = $block->rawCode;
            } elseif ($block->attributes->isTeardown()) {
                $teardownCode[] = $block->rawCode;
            } elseif ($block->attributes->hasGroup() && $block->attributes->group !== null) {
                $groupedBlocks[$block->attributes->group][] = $block;
            } else {
                $normalBlocks[] = $block;
            }
        }

        $setup    = $setupCode !== [] ? implode("\n", $setupCode) : null;
        $teardown = $teardownCode !== [] ? implode("\n", $teardownCode) : null;

        return [$setup, $teardown, $normalBlocks, $groupedBlocks];
    }

    private function resolveBlockBootstrap(CodeBlock $block): ?string
    {
        if ($this->bootstrapResolver === null || !$block->attributes->hasBootstraps()) {
            return null;
        }

        return $this->bootstrapResolver->resolve($block->attributes->bootstraps);
    }

    private function preSyntaxCheck(string $filePath, ?CodeBlock $block = null): ?ExecutionResult
    {
        $output   = [];
        $exitCode = 0;
        exec(PHP_BINARY.' -l '.escapeshellarg($filePath).' 2>&1', $output, $exitCode);

        if ($exitCode === 0) {
            return null;
        }

        $this->cleanup($filePath);

        return new ExecutionResult(
            passed: false,
            codeBlock: $block ?? new CodeBlock(file: '', startLine: 0, rawCode: '', executableCode: '', attributes: new \TestFlowLabs\DocTest\CodeBlock\Attributes(), assertions: []),
            error: 'Syntax error: '.implode("\n", $output),
        );
    }

    private function cleanup(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
