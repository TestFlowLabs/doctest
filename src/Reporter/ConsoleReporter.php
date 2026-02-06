<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Reporter;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\Executor\ExecutionResult;

final class ConsoleReporter
{
    private const int MAX_PREVIEW_LENGTH = 60;

    private readonly ErrorFormatter $errorFormatter;

    private int $totalBlocks = 0;

    private int $currentBlock = 0;

    public function __construct(
        private readonly OutputInterface $output = new ConsoleOutput(),
    ) {
        $this->errorFormatter = new ErrorFormatter();
    }

    public function setTotalBlocks(int $total): void
    {
        $this->totalBlocks = $total;
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

        $preview = $this->codePreview($result->codeBlock);
        $location = ":{$result->codeBlock->startLine}";

        if ($result->skipped) {
            $this->output->writeln("  <fg=gray>{$location}</> <fg=gray>⊘</> {$preview}{$progress}");
            $this->flush();

            return;
        }

        if ($result->passed) {
            $duration = sprintf('<fg=gray>%.2fs</>', $result->duration);
            $this->output->writeln("  <fg=gray>{$location}</> <fg=green>✔</> {$preview}{$progress} {$duration}");
            $this->flush();

            return;
        }

        $duration = sprintf('<fg=gray>%.2fs</>', $result->duration);
        $failLocation = "{$result->codeBlock->file}:{$result->codeBlock->startLine}";
        $line = "  <fg=gray>{$failLocation}</> <fg=red>✖</> {$preview}{$progress} {$duration}";

        $this->output->writeln($line);

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

        $this->output->writeln('');
        $this->output->writeln(str_repeat('-', 40));

        $summary = "Blocks: {$total}  <fg=green>Passed: {$passed}</>  ";

        if ($failed > 0) {
            $summary .= "<fg=red>Failed: {$failed}</>  ";
        }

        if ($skipped > 0) {
            $summary .= "<fg=gray>Skipped: {$skipped}</>  ";
        }

        $summary .= sprintf('Duration: %.2fs', $duration);
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

    private function codePreview(CodeBlock $codeBlock): string
    {
        $firstLine = trim(explode("\n", $codeBlock->rawCode)[0]);

        if (mb_strlen($firstLine) > self::MAX_PREVIEW_LENGTH) {
            return mb_substr($firstLine, 0, self::MAX_PREVIEW_LENGTH - 3) . '...';
        }

        return $firstLine;
    }
}
