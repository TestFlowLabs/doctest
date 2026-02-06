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

            // Check if class name matches (with or without leading backslash)
            if (! str_ends_with($actualClass, $expectedClass)) {
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

    private function executeNormal(CodeBlock $block): ExecutionResult
    {
        $filePath = $this->codeGenerator->generate($block);
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

        /** @var array<array{type: string, expected?: string, actual?: string, expression?: string, passed?: bool, line?: int}> $results */
        $results = json_decode($processResult->stderr, true) ?? [];

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

                if (preg_match($pattern, $actual) !== 1) {
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
                $actualDecoded = json_decode($actual, true);

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
        }

        $actualOutput = $capturedOutput !== [] ? implode('', $capturedOutput) : ($processResult->stdout !== '' ? $processResult->stdout : null);

        return new ExecutionResult(
            passed: true,
            codeBlock: $block,
            actualOutput: $actualOutput,
            duration: $processResult->duration,
        );
    }

    private function cleanup(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
