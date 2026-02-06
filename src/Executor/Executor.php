<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Executor;

use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\Comparison\DiffGenerator;
use TestFlowLabs\DocTest\Comparison\OutputComparator;

final readonly class Executor
{
    private CodeGenerator $codeGenerator;

    private ProcessRunner $processRunner;

    private OutputComparator $comparator;

    private DiffGenerator $diffGenerator;

    public function __construct(
        int $timeout = 5,
        string $memoryLimit = '128M',
    ) {
        $this->codeGenerator = new CodeGenerator();
        $this->processRunner = new ProcessRunner($timeout, $memoryLimit);
        $this->comparator = new OutputComparator();
        $this->diffGenerator = new DiffGenerator();
    }

    /**
     * @param array<CodeBlock> $blocks
     *
     * @return array<ExecutionResult>
     */
    public function executeAll(array $blocks): array
    {
        [$setup, $teardown, $normalBlocks, $groupedBlocks] = $this->collectSetupTeardownAndGroups($blocks);

        $results = [];

        foreach ($normalBlocks as $block) {
            if ($block->attributes->isIgnore()) {
                $results[] = new ExecutionResult(passed: true, codeBlock: $block, skipped: true);

                continue;
            }

            if ($block->attributes->isNoRun()) {
                $results[] = $this->syntaxCheck($block);

                continue;
            }

            if ($block->attributes->isParseError()) {
                $results[] = $this->executeParseError($block);

                continue;
            }

            if ($block->attributes->isThrows()) {
                $results[] = $this->executeThrows($block);

                continue;
            }

            $results[] = $this->executeNormal($block, $setup, $teardown);
        }

        foreach ($groupedBlocks as $groupBlocks) {
            $results = array_merge($results, $this->executeGroupBlocks($groupBlocks, $setup, $teardown));
        }

        return $results;
    }

    /**
     * @param array<CodeBlock> $blocks
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
        $tempFile = tempnam(sys_get_temp_dir(), 'doctest_') . '.php';
        file_put_contents($tempFile, "<?php\n" . $block->executableCode);

        $output = [];
        $exitCode = 0;
        exec(PHP_BINARY . ' -l ' . escapeshellarg($tempFile) . ' 2>&1', $output, $exitCode);
        unlink($tempFile);

        if ($exitCode === 0) {
            return new ExecutionResult(passed: true, codeBlock: $block);
        }

        return new ExecutionResult(
            passed: false,
            codeBlock: $block,
            error: 'Syntax error: ' . implode("\n", $output),
        );
    }

    private function executeParseError(CodeBlock $block): ExecutionResult
    {
        $filePath = $this->codeGenerator->generate($block);
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
        $filePath = $this->codeGenerator->generate($block);
        $processResult = $this->processRunner->run($filePath);
        $this->cleanup($filePath);

        /** @var array{thrown?: bool, class?: string, message?: string} $data */
        $data = json_decode($processResult->stderr, true) ?? [];

        if (! isset($data['thrown']) || $data['thrown'] !== true) {
            return new ExecutionResult(
                passed: false,
                codeBlock: $block,
                error: 'Expected exception ' . ($block->attributes->throwsClass ?? 'Throwable') . ' but none was thrown',
                duration: $processResult->duration,
            );
        }

        if ($block->attributes->throwsClass !== null && isset($data['class'])) {
            $actualClass = $data['class'];
            $expectedClass = $block->attributes->throwsClass;

            $normalizedActual = ltrim($actualClass, '\\');
            $normalizedExpected = ltrim($expectedClass, '\\');

            if ($normalizedActual !== $normalizedExpected) {
                return new ExecutionResult(
                    passed: false,
                    codeBlock: $block,
                    error: "Expected exception {$expectedClass} but got {$actualClass}",
                    duration: $processResult->duration,
                );
            }
        }

        if ($block->attributes->throwsMessage !== null && isset($data['message'])) {
            if (! str_contains($data['message'], $block->attributes->throwsMessage)) {
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

    private function executeNormal(CodeBlock $block, ?string $setup = null, ?string $teardown = null): ExecutionResult
    {
        $filePath = $this->codeGenerator->generate($block, setup: $setup, teardown: $teardown);
        $processResult = $this->processRunner->run($filePath);
        $this->cleanup($filePath);

        // Handle process crash or timeout (no valid JSON in stderr)
        if ($processResult->exitCode !== 0) {
            $decoded = json_decode($processResult->stderr, true);

            if (! is_array($decoded)) {
                $errorMessage = $processResult->stderr !== ''
                    ? 'Process failed (exit code ' . $processResult->exitCode . '): ' . $processResult->stderr
                    : 'Process failed with exit code ' . $processResult->exitCode;

                return new ExecutionResult(
                    passed: false,
                    codeBlock: $block,
                    error: $errorMessage,
                    duration: $processResult->duration,
                );
            }
        }

        $decoded = json_decode($processResult->stderr, true);

        /** @var array<array{type: string, expected?: string, actual?: string, expression?: string, passed?: bool, line?: int}> $results */
        $results = is_array($decoded) ? $decoded : [];

        return $this->evaluateResults($block, $results, $processResult);
    }

    /**
     * @param array<array{type: string, expected?: string, actual?: string, expression?: string, passed?: bool, line?: int}> $results
     */
    private function evaluateResults(CodeBlock $block, array $results, ProcessResult $processResult): ExecutionResult
    {
        $capturedOutput = [];

        foreach ($results as $result) {
            if ($result['type'] === 'output') {
                $expected = $result['expected'] ?? '';
                $actual = $result['actual'] ?? '';
                $capturedOutput[] = $actual;
                $comparison = $this->comparator->compare($expected, $actual);

                if (! $comparison->passed) {
                    $diff = $this->diffGenerator->generate($comparison->normalizedExpected, $comparison->normalizedActual);

                    return new ExecutionResult(
                        passed: false,
                        codeBlock: $block,
                        actualOutput: $actual,
                        expectedOutput: $expected,
                        diff: $diff,
                        duration: $processResult->duration,
                    );
                }
            }

            if ($result['type'] === 'output_contains') {
                $expected = $result['expected'] ?? '';
                $actual = $result['actual'] ?? '';
                $capturedOutput[] = $actual;

                if (! str_contains($actual, $expected)) {
                    return new ExecutionResult(
                        passed: false,
                        codeBlock: $block,
                        actualOutput: $actual,
                        expectedOutput: $expected,
                        error: "Output does not contain: {$expected}",
                        duration: $processResult->duration,
                    );
                }
            }

            if ($result['type'] === 'output_matches') {
                $pattern = $result['expected'] ?? '';
                $actual = $result['actual'] ?? '';
                $capturedOutput[] = $actual;

                $matchResult = @preg_match($pattern, $actual);

                if ($matchResult === false) {
                    return new ExecutionResult(
                        passed: false,
                        codeBlock: $block,
                        error: "Invalid regex pattern: {$pattern}",
                        duration: $processResult->duration,
                    );
                }

                if ($matchResult !== 1) {
                    return new ExecutionResult(
                        passed: false,
                        codeBlock: $block,
                        actualOutput: $actual,
                        expectedOutput: $pattern,
                        error: "Output does not match pattern: {$pattern}",
                        duration: $processResult->duration,
                    );
                }
            }

            if ($result['type'] === 'output_json') {
                $expectedJson = $result['expected'] ?? '';
                $actual = $result['actual'] ?? '';
                $capturedOutput[] = $actual;

                $expectedDecoded = json_decode($expectedJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return new ExecutionResult(
                        passed: false,
                        codeBlock: $block,
                        error: 'Expected JSON is invalid: ' . json_last_error_msg(),
                        duration: $processResult->duration,
                    );
                }

                $actualDecoded = json_decode($actual, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return new ExecutionResult(
                        passed: false,
                        codeBlock: $block,
                        actualOutput: $actual,
                        error: 'Actual JSON output is invalid: ' . json_last_error_msg(),
                        duration: $processResult->duration,
                    );
                }

                if ($expectedDecoded !== $actualDecoded) {
                    return new ExecutionResult(
                        passed: false,
                        codeBlock: $block,
                        actualOutput: $actual,
                        expectedOutput: $expectedJson,
                        error: 'JSON output does not match expected structure',
                        duration: $processResult->duration,
                    );
                }
            }

            if ($result['type'] === 'expect') {
                if (! ($result['passed'] ?? false)) {
                    return new ExecutionResult(
                        passed: false,
                        codeBlock: $block,
                        error: 'Expect assertion failed: ' . ($result['expression'] ?? ''),
                        duration: $processResult->duration,
                    );
                }
            }

            if ($result['type'] === 'result_comment') {
                $expected = $result['expected'] ?? '';
                $actual = $result['actual'] ?? '';

                if ($expected !== $actual) {
                    return new ExecutionResult(
                        passed: false,
                        codeBlock: $block,
                        error: "result_comment assertion failed: expected {$expected} but got {$actual}",
                        duration: $processResult->duration,
                    );
                }
            }
        }

        $actualOutput = $capturedOutput !== [] ? implode('', $capturedOutput) : ($processResult->stdout !== '' ? $processResult->stdout : null);

        return new ExecutionResult(
            passed: true,
            codeBlock: $block,
            actualOutput: $actualOutput,
            duration: $processResult->duration,
        );
    }

    /**
     * @param array<CodeBlock> $blocks
     *
     * @return array<ExecutionResult>
     */
    private function executeGroupBlocks(array $blocks, ?string $setup = null, ?string $teardown = null): array
    {
        $filePath = $this->codeGenerator->generateGroup($blocks, setup: $setup, teardown: $teardown);
        $processResult = $this->processRunner->run($filePath);
        $this->cleanup($filePath);

        if ($processResult->exitCode !== 0) {
            $decoded = json_decode($processResult->stderr, true);

            if (! is_array($decoded)) {
                $errorMessage = $processResult->stderr !== ''
                    ? 'Process failed (exit code ' . $processResult->exitCode . '): ' . $processResult->stderr
                    : 'Process failed with exit code ' . $processResult->exitCode;

                return array_map(
                    fn(CodeBlock $block) => new ExecutionResult(
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

        /** @var array<array{type: string, expected?: string, actual?: string, expression?: string, passed?: bool, line?: int}> $allResults */
        $allResults = is_array($decoded) ? $decoded : [];

        return $this->mapResultsToBlocks($blocks, $allResults, $processResult);
    }

    /**
     * @param array<CodeBlock> $blocks
     * @param array<array{type: string, expected?: string, actual?: string, expression?: string, passed?: bool, line?: int}> $allResults
     *
     * @return array<ExecutionResult>
     */
    private function mapResultsToBlocks(array $blocks, array $allResults, ProcessResult $processResult): array
    {
        $parser = new \TestFlowLabs\DocTest\Assertion\AssertionParser();

        // Count expected assertions per block to partition results
        $assertionCounts = [];
        foreach ($blocks as $block) {
            $parsed = $parser->parse($block->rawCode);
            $count = 0;
            foreach ($parsed->segments as $segment) {
                if ($segment->outputAssertion !== null) {
                    $count++;
                }
            }
            $count += count($parsed->expects);
            $count += count($parsed->resultComments);
            $assertionCounts[] = $count;
        }

        $results = [];
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
     * @param array<CodeBlock> $blocks
     *
     * @return array{?string, ?string, array<CodeBlock>, array<string, array<CodeBlock>>}
     */
    private function collectSetupTeardownAndGroups(array $blocks): array
    {
        $setupCode = [];
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

        $setup = $setupCode !== [] ? implode("\n", $setupCode) : null;
        $teardown = $teardownCode !== [] ? implode("\n", $teardownCode) : null;

        return [$setup, $teardown, $normalBlocks, $groupedBlocks];
    }

    private function cleanup(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
