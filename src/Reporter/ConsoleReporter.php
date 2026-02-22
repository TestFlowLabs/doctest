<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Reporter;

use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\Updater\AssertionUpdate;
use TestFlowLabs\DocTest\Executor\ExecutionResult;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class ConsoleReporter
{
    private const int MAX_PREVIEW_LENGTH = 60;

    private readonly ErrorFormatter $errorFormatter;
    private int $totalBlocks     = 0;
    private int $currentBlock    = 0;
    private int $lineNumberWidth = 1;
    private int $parallelWorkers = 1;

    public function __construct(
        private readonly OutputInterface $output = new ConsoleOutput(),
    ) {
        $this->errorFormatter = new ErrorFormatter();
    }

    public function setTotalBlocks(int $total): void
    {
        $this->totalBlocks = $total;
    }

    public function setMaxLineNumber(int $maxLine): void
    {
        $this->lineNumberWidth = max(1, strlen((string) $maxLine));
    }

    public function setParallelWorkers(int $workers): void
    {
        $this->parallelWorkers = $workers;
    }

    public function reportFile(string $filePath): void
    {
        $this->output->writeln('');
        $this->output->writeln("<options=bold>{$filePath}</>");
        $this->flush();
    }

    public function reportResult(ExecutionResult $result): void
    {
        $this->currentBlock++;
        $progress = $this->totalBlocks > 0
            ? " [{$this->currentBlock}/{$this->totalBlocks}]"
            : '';

        $preview    = $this->codePreview($result->codeBlock);
        $paddedLine = str_pad((string) $result->codeBlock->startLine, $this->lineNumberWidth, ' ', STR_PAD_LEFT);
        $location   = ":{$paddedLine}";

        if ($result->skipped) {
            $this->output->writeln("  <fg=gray>{$location}</> <fg=gray>⊘</> {$preview}{$progress}");
            $this->flush();

            return;
        }

        if ($result->passed) {
            $duration = sprintf('<fg=gray>%.2fs</>', $result->duration);
            $this->output->writeln("  <fg=gray>{$location}</> <fg=green>✔</> {$preview}{$progress} {$duration}");
            $this->writeAssertionDetails($result);
            $this->writeDebugOutputs($result);
            $this->flush();

            return;
        }

        $duration = sprintf('<fg=gray>%.2fs</>', $result->duration);
        $line     = "  <fg=gray>{$location}</> <fg=red>✖</> {$preview}{$progress} {$duration}";

        $this->output->writeln($line);

        $this->writeAssertionDetails($result);
        $this->writeDebugOutputs($result);

        if ($result->error !== null) {
            $this->output->writeln("    {$result->error}");
        }

        if ($this->output->isVeryVerbose()) {
            $this->output->writeln('    Source:');
            foreach (explode("\n", $result->codeBlock->rawCode) as $codeLine) {
                $this->output->writeln("      {$codeLine}");
            }
        }

        if ($result->diff !== null) {
            $this->output->writeln('');
            $this->output->writeln($this->errorFormatter->format($result));
        }

        $this->flush();
    }

    /**
     * @param  array<ExecutionResult>  $results
     */
    public function reportSummary(array $results, float $duration): void
    {
        $total   = count($results);
        $passed  = 0;
        $failed  = 0;
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

        $this->output->writeln('');
        $this->output->writeln(str_repeat('-', 40));

        $summary = "Blocks: {$total}  <fg=green>Passed: {$passed}</>  ";

        if ($failed > 0) {
            $summary .= "<fg=red>Failed: {$failed}</>  ";
        }

        if ($skipped > 0) {
            $summary .= "<fg=gray>Skipped: {$skipped}</>  ";
        }

        if ($this->parallelWorkers > 1) {
            $summary .= "Parallel: {$this->parallelWorkers}  ";
        }

        $summary .= sprintf('Duration: %.2fs', $duration);
        $this->output->writeln($summary);
    }

    /**
     * @param  array<AssertionUpdate>  $updates
     */
    public function reportUpdate(ExecutionResult $result, array $updates): void
    {
        $this->currentBlock++;
        $progress = $this->totalBlocks > 0
            ? " [{$this->currentBlock}/{$this->totalBlocks}]"
            : '';

        $preview    = $this->codePreview($result->codeBlock);
        $paddedLine = str_pad((string) $result->codeBlock->startLine, $this->lineNumberWidth, ' ', STR_PAD_LEFT);
        $location   = ":{$paddedLine}";

        $this->output->writeln("  <fg=gray>{$location}</> <fg=cyan>✎</> {$preview}{$progress}");

        if ($this->output->isVerbose()) {
            foreach ($updates as $update) {
                $short = mb_strlen($update->newValue) > 40
                    ? mb_substr($update->newValue, 0, 37).'...'
                    : $update->newValue;
                $this->output->writeln("       <fg=cyan>✎</> <fg=gray>{$update->assertionType}: {$short}</>");
            }
        }

        $this->flush();
    }

    public function reportUpdateSummary(int $totalUpdated, int $filesUpdated, float $duration): void
    {
        $this->output->writeln('');
        $this->output->writeln(str_repeat('-', 40));

        $summary = "Updated: {$totalUpdated} assertion".($totalUpdated !== 1 ? 's' : '');
        $summary .= " in {$filesUpdated} file".($filesUpdated !== 1 ? 's' : '');
        $summary .= sprintf('  Duration: %.2fs', $duration);

        $this->output->writeln($summary);
    }

    private function flush(): void
    {
        if ($this->output instanceof ConsoleOutput) {
            $stream = $this->output->getStream();
            if (is_resource($stream)) {
                fflush($stream);
            }
        }
    }

    private function writeAssertionDetails(ExecutionResult $result): void
    {
        if (!$this->output->isVerbose() || $result->assertionDetails === []) {
            return;
        }

        foreach ($result->assertionDetails as $detail) {
            $icon = $detail->passed ? '<fg=green>✔</>' : '<fg=red>✖</>';
            $info = match ($detail->type) {
                'result_comment' => $detail->expression !== null
                    ? "{$detail->expression} => {$detail->actual}"
                    : "=> {$detail->actual}",
                'expect' => $detail->expression ?? $detail->expected,
                default  => $detail->expected === $detail->actual
                    ? "{$detail->type}: {$detail->actual}"
                    : "{$detail->type}: expected {$detail->expected}, got {$detail->actual}",
            };

            $this->output->writeln("       {$icon} <fg=gray>{$info}</>");
        }
    }

    private function writeDebugOutputs(ExecutionResult $result): void
    {
        if ($result->debugOutputs === []) {
            return;
        }

        foreach ($result->debugOutputs as $debug) {
            $this->output->writeln("       <fg=yellow>dd</> <fg=gray>{$debug->expression} => {$debug->value}</>");
        }
    }

    private function codePreview(CodeBlock $codeBlock): string
    {
        $firstLine = trim(explode("\n", $codeBlock->rawCode)[0]);

        if (mb_strlen($firstLine) > self::MAX_PREVIEW_LENGTH) {
            return mb_substr($firstLine, 0, self::MAX_PREVIEW_LENGTH - 3).'...';
        }

        return $firstLine;
    }
}
