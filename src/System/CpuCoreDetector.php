<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\System;

final class CpuCoreDetector
{
    public static function detect(): int
    {
        // Linux
        if (is_file('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');

            if ($cpuinfo !== false) {
                $count = substr_count($cpuinfo, 'processor');

                if ($count > 0) {
                    return $count;
                }
            }
        }

        // macOS
        if (PHP_OS_FAMILY === 'Darwin') {
            $result = shell_exec('sysctl -n hw.logicalcpu');

            if ($result !== null && $result !== false) {
                $cores = (int) trim($result);

                if ($cores > 0) {
                    return $cores;
                }
            }
        }

        // Windows
        $envCores = getenv('NUMBER_OF_PROCESSORS');

        if ($envCores !== false) {
            $cores = (int) $envCores;

            if ($cores > 0) {
                return $cores;
            }
        }

        // Generic fallback via nproc
        $result = shell_exec('nproc');

        if ($result !== null && $result !== false) {
            $cores = (int) trim($result);

            if ($cores > 0) {
                return $cores;
            }
        }

        return 1;
    }
}
