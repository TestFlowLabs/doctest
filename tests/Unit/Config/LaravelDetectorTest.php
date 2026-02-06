<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\Config\LaravelDetector;

final class LaravelDetectorTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/doctest_detect_'.bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    #[Test]
    public function detects_laravel_when_bootstrap_app_exists(): void
    {
        mkdir($this->tempDir.'/bootstrap', 0777, true);
        file_put_contents($this->tempDir.'/bootstrap/app.php', '<?php return new stdClass();');

        $detector = new LaravelDetector();

        $this->assertTrue($detector->isLaravel($this->tempDir));
    }

    #[Test]
    public function returns_false_when_bootstrap_missing(): void
    {
        mkdir($this->tempDir, 0777, true);

        $detector = new LaravelDetector();

        $this->assertFalse($detector->isLaravel($this->tempDir));
    }

    #[Test]
    public function detection_works_from_project_root(): void
    {
        mkdir($this->tempDir.'/bootstrap', 0777, true);
        mkdir($this->tempDir.'/app', 0777, true);
        file_put_contents($this->tempDir.'/bootstrap/app.php', '<?php return new stdClass();');
        file_put_contents($this->tempDir.'/artisan', '<?php // artisan');

        $detector = new LaravelDetector();

        $this->assertTrue($detector->isLaravel($this->tempDir));
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($dir);
    }
}
