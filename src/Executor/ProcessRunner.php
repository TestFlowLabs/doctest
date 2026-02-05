<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Executor;

final readonly class ProcessRunner
{
    public function __construct(
        private int $timeout,
        private string $memoryLimit,
    ) {}

    public function run(string $phpFilePath): ProcessResult
    {
        $command = [
            PHP_BINARY,
            '-d', 'memory_limit=' . $this->memoryLimit,
            $phpFilePath,
        ];

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $startTime = microtime(true);

        $process = proc_open($command, $descriptors, $pipes);

        if (! is_resource($process)) {
            return new ProcessResult(
                stdout: '',
                stderr: 'Failed to start process',
                exitCode: 1,
                duration: microtime(true) - $startTime,
            );
        }

        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $timedOut = false;

        while (true) {
            $status = proc_get_status($process);

            if (! $status['running']) {
                $stdout .= stream_get_contents($pipes[1]) ?: '';
                $stderr .= stream_get_contents($pipes[2]) ?: '';

                break;
            }

            if ((microtime(true) - $startTime) >= $this->timeout) {
                $timedOut = true;
                /** @var int $pid */
                $pid = $status['pid'];
                posix_kill($pid, 9);

                break;
            }

            $stdout .= stream_get_contents($pipes[1]) ?: '';
            $stderr .= stream_get_contents($pipes[2]) ?: '';

            usleep(10_000);
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        if ($timedOut) {
            $exitCode = 137;
            proc_close($process);
        } else {
            $exitCode = $status['running'] ? proc_close($process) : $status['exitcode'];
        }

        $duration = microtime(true) - $startTime;

        return new ProcessResult(
            stdout: $stdout,
            stderr: $stderr,
            exitCode: $exitCode,
            duration: $duration,
        );
    }
}
