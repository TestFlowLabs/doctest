<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Executor;

final readonly class WorkerPool
{
    public function __construct(
        private int $maxWorkers,
        private int $timeout,
        private string $memoryLimit,
    ) {
        if ($maxWorkers < 1) {
            throw new \InvalidArgumentException("maxWorkers must be at least 1, got: {$maxWorkers}");
        }
    }

    /**
     * @param  array<WorkItem>  $items
     *
     * @return array<int, ProcessResult> keyed by WorkItem index
     */
    public function run(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $queue   = $items;
        $running = [];
        $results = [];

        while ($queue !== [] || $running !== []) {
            // Fill up to maxWorkers slots
            while ($queue !== [] && count($running) < $this->maxWorkers) {
                $item = array_shift($queue);
                $slot = $this->startProcess($item);

                if ($slot !== null) {
                    $running[$item->index] = $slot;
                }
            }

            // Poll running processes
            foreach ($running as $index => $slot) {
                $status = proc_get_status($slot['process']);

                // Read available output (non-blocking)
                $slot['stdout'] .= stream_get_contents($slot['pipes'][1]) ?: '';
                $slot['stderr'] .= stream_get_contents($slot['pipes'][2]) ?: '';

                if (!$status['running']) {
                    // Final read to capture any remaining buffered output
                    $slot['stdout'] .= stream_get_contents($slot['pipes'][1]) ?: '';
                    $slot['stderr'] .= stream_get_contents($slot['pipes'][2]) ?: '';

                    $results[$index] = $this->collectResult($slot, $status['exitcode']);
                    $this->closeSlot($slot);
                    unset($running[$index]);

                    continue;
                }

                // Check timeout
                if ((microtime(true) - $slot['startTime']) >= $this->timeout) {
                    if (function_exists('posix_kill')) {
                        /** @var int $pid */
                        $pid = $status['pid'];
                        posix_kill($pid, 9);
                    } else {
                        proc_terminate($slot['process'], 9);
                    }

                    $results[$index] = $this->collectResult($slot, 137);
                    $this->closeSlot($slot);
                    unset($running[$index]);

                    continue;
                }

                $running[$index] = $slot;
            }

            if ($running !== []) {
                usleep(5_000);
            }
        }

        return $results;
    }

    /**
     * @return array{process: resource, pipes: array<int, resource>, stdout: string, stderr: string, startTime: float}|null
     */
    private function startProcess(WorkItem $item): ?array
    {
        $command = [
            PHP_BINARY,
            '-d', 'memory_limit='.$this->memoryLimit,
            $item->filePath,
        ];

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            return null;
        }

        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        return [
            'process'   => $process,
            'pipes'     => $pipes,
            'stdout'    => '',
            'stderr'    => '',
            'startTime' => microtime(true),
        ];
    }

    /**
     * @param  array{process: resource, pipes: array<int, resource>, stdout: string, stderr: string, startTime: float}  $slot
     */
    private function collectResult(array $slot, int $exitCode): ProcessResult
    {
        return new ProcessResult(
            stdout: $slot['stdout'],
            stderr: $slot['stderr'],
            exitCode: $exitCode,
            duration: microtime(true) - $slot['startTime'],
        );
    }

    /**
     * @param  array{process: resource, pipes: array<int, resource>, stdout: string, stderr: string, startTime: float}  $slot
     */
    private function closeSlot(array $slot): void
    {
        @fclose($slot['pipes'][1]);
        @fclose($slot['pipes'][2]);
        proc_close($slot['process']);
    }
}
