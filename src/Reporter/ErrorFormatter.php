<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Reporter;

use TestFlowLabs\DocTest\Executor\ExecutionResult;

final readonly class ErrorFormatter
{
    public function format(ExecutionResult $result): string
    {
        $lines = [];

        // Location header
        $lines[] = sprintf(
            '  %s:%d',
            $result->codeBlock->file,
            $result->codeBlock->startLine,
        );
        $lines[] = '';

        // Code context
        $lines[] = $this->renderCodeContext($result);

        // Error or assertion failure
        if ($result->error !== null) {
            $lines[] = '  Error: ' . $result->error;
        }

        if ($result->expectedOutput !== null && $result->actualOutput !== null) {
            $lines[] = '  Expected: ' . $result->expectedOutput;
            $lines[] = '  Actual:   ' . $result->actualOutput;
        }

        if ($result->diff !== null) {
            $lines[] = '';
            $lines[] = '  Diff:';
            foreach (explode("\n", $result->diff) as $diffLine) {
                $lines[] = '    ' . $diffLine;
            }
        }

        return implode("\n", $lines);
    }

    private function renderCodeContext(ExecutionResult $result): string
    {
        $codeLines = explode("\n", $result->codeBlock->rawCode);
        $startLine = $result->codeBlock->startLine;
        $output = [];

        foreach ($codeLines as $index => $codeLine) {
            $lineNumber = $startLine + $index;
            $output[] = sprintf('  %4d | %s', $lineNumber, $codeLine);
        }

        return implode("\n", $output);
    }
}
