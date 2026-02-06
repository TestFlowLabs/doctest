<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Config;

final readonly class LaravelDetector
{
    public function isLaravel(string $projectRoot): bool
    {
        return file_exists($projectRoot.'/bootstrap/app.php');
    }
}
