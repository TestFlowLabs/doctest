<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Reporter;

use TestFlowLabs\DocTest\Executor\ExecutionResult;

final class JsonReporter
{
    /**
     * @param array<ExecutionResult> $results
     */
    public function generate(array $results): string
    {
        $grouped = $this->groupByFile($results);
        $files = [];

        foreach ($grouped as $file => $fileResults) {
            $blocks = [];

            foreach ($fileResults as $result) {
                $block = [
                    'line' => $result->codeBlock->startLine,
                    'passed' => $result->passed,
                    'skipped' => $result->skipped,
                    'duration' => $result->duration,
                ];

                if ($result->error !== null) {
                    $block['error'] = $result->error;
                }

                if ($result->expectedOutput !== null) {
                    $block['expected'] = $result->expectedOutput;
                }

                if ($result->actualOutput !== null) {
                    $block['actual'] = $result->actualOutput;
                }

                if ($result->diff !== null) {
                    $block['diff'] = $result->diff;
                }

                $blocks[] = $block;
            }

            $files[] = [
                'file' => $file,
                'blocks' => $blocks,
            ];
        }

        $passed = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($results as $result) {
            if ($result->skipped) {
                $skipped++;
            } elseif ($result->passed) {
                $passed++;
            } else {
                $failed++;
            }
        }

        $data = [
            'files' => $files,
            'summary' => [
                'total' => count($results),
                'passed' => $passed,
                'failed' => $failed,
                'skipped' => $skipped,
            ],
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * @param array<ExecutionResult> $results
     */
    public function generateToFile(array $results, string $filePath): void
    {
        file_put_contents($filePath, $this->generate($results));
    }

    /**
     * @param array<ExecutionResult> $results
     *
     * @return array<string, array<ExecutionResult>>
     */
    private function groupByFile(array $results): array
    {
        $grouped = [];

        foreach ($results as $result) {
            $grouped[$result->codeBlock->file][] = $result;
        }

        return $grouped;
    }
}
