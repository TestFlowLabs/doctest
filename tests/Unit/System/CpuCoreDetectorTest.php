<?php

declare(strict_types=1);

use TestFlowLabs\DocTest\System\CpuCoreDetector;

test('detect returns a positive integer', function (): void {
    $cores = CpuCoreDetector::detect();

    expect($cores)->toBeInt()->toBeGreaterThanOrEqual(1);
});

test('detect returns consistent results on repeated calls', function (): void {
    $first  = CpuCoreDetector::detect();
    $second = CpuCoreDetector::detect();

    expect($first)->toBe($second);
});
