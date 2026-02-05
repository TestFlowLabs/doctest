<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Reporter;

use TestFlowLabs\DocTest\Executor\ExecutionResult;

final class ConsoleReporter
{
    /** @var resource */
    private $output;

    private readonly ErrorFormatter $errorFormatter;

    /**
     * @param resource $output
     */
    public function __construct(
        $output = null,
        private readonly bool $colors = true,
    ) {
        $this->output = $output ?? STDOUT;
        $this->errorFormatter = new ErrorFormatter();
    }

    public function reportFile(string $filePath): void
    {
        $this->write("\n" . $this->bold($filePath) . "\n");
    }

    public function reportResult(ExecutionResult $result): void
    {
        if ($result->skipped) {
            $this->write('  ' . $this->gray('[SKIP]') . " Line {$result->codeBlock->startLine}\n");

            return;
        }

        if ($result->passed) {
            $this->write('  ' . $this->green('[PASS]') . " Line {$result->codeBlock->startLine}\n");

            return;
        }

        $this->write('  ' . $this->red('[FAIL]') . " Line {$result->codeBlock->startLine}\n");

        if ($result->error !== null) {
            $this->write('    ' . $result->error . "\n");
        }

        if ($result->diff !== null) {
            $this->write("\n" . $this->errorFormatter->format($result) . "\n");
        }
    }

    /**
     * @param array<ExecutionResult> $results
     */
    public function reportSummary(array $results, float $duration): void
    {
        $total = count($results);
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

        $this->write("\n");
        $this->write(str_repeat('-', 40) . "\n");
        $this->write("Blocks: {$total}  ");
        $this->write($this->green("Passed: {$passed}") . '  ');

        if ($failed > 0) {
            $this->write($this->red("Failed: {$failed}") . '  ');
        }

        if ($skipped > 0) {
            $this->write($this->gray("Skipped: {$skipped}") . '  ');
        }

        $this->write(sprintf("Duration: %.2fs\n", $duration));
    }

    private function write(string $text): void
    {
        fwrite($this->output, $text);
    }

    private function green(string $text): string
    {
        return $this->color($text, '32');
    }

    private function red(string $text): string
    {
        return $this->color($text, '31');
    }

    private function gray(string $text): string
    {
        return $this->color($text, '90');
    }

    private function bold(string $text): string
    {
        return $this->color($text, '1');
    }

    private function color(string $text, string $code): string
    {
        if (! $this->colors) {
            return $text;
        }

        return "\033[{$code}m{$text}\033[0m";
    }
}
