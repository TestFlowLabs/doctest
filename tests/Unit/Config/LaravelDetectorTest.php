<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Config;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Config\LaravelDetector;

final class LaravelDetectorTest extends TestCase
{
    #[Test]
    public function detects_laravel_when_bootstrap_app_exists(): void
    {
        $tempDir = sys_get_temp_dir() . '/doctest_detect_' . uniqid();
        mkdir($tempDir . '/bootstrap', 0777, true);
        file_put_contents($tempDir . '/bootstrap/app.php', '<?php return new stdClass();');

        $detector = new LaravelDetector();

        $this->assertTrue($detector->isLaravel($tempDir));

        unlink($tempDir . '/bootstrap/app.php');
        rmdir($tempDir . '/bootstrap');
        rmdir($tempDir);
    }

    #[Test]
    public function returns_false_when_bootstrap_missing(): void
    {
        $tempDir = sys_get_temp_dir() . '/doctest_detect_' . uniqid();
        mkdir($tempDir, 0777, true);

        $detector = new LaravelDetector();

        $this->assertFalse($detector->isLaravel($tempDir));

        rmdir($tempDir);
    }

    #[Test]
    public function detection_works_from_project_root(): void
    {
        $tempDir = sys_get_temp_dir() . '/doctest_detect_' . uniqid();
        mkdir($tempDir . '/bootstrap', 0777, true);
        mkdir($tempDir . '/app', 0777, true);
        file_put_contents($tempDir . '/bootstrap/app.php', '<?php return new stdClass();');
        file_put_contents($tempDir . '/artisan', '<?php // artisan');

        $detector = new LaravelDetector();

        $this->assertTrue($detector->isLaravel($tempDir));

        unlink($tempDir . '/artisan');
        unlink($tempDir . '/bootstrap/app.php');
        rmdir($tempDir . '/app');
        rmdir($tempDir . '/bootstrap');
        rmdir($tempDir);
    }
}
