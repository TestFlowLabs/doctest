<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Laravel;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Laravel\LaravelBootstrap;

final class LaravelBootstrapTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/doctest_laravel_' . bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    #[Test]
    public function detects_laravel_when_bootstrap_app_exists(): void
    {
        $this->createLaravelStructure();

        $bootstrap = new LaravelBootstrap($this->tempDir);

        $this->assertTrue($bootstrap->isLaravelProject());
    }

    #[Test]
    public function detects_no_laravel_without_bootstrap(): void
    {
        mkdir($this->tempDir, 0777, true);

        $bootstrap = new LaravelBootstrap($this->tempDir);

        $this->assertFalse($bootstrap->isLaravelProject());
    }

    #[Test]
    public function generates_bootstrap_code_for_laravel(): void
    {
        $this->createLaravelStructure();

        $bootstrap = new LaravelBootstrap($this->tempDir);
        $code = $bootstrap->getBootstrapCode();

        $this->assertNotNull($code);
        $this->assertStringContainsString('bootstrap/app.php', $code);
    }

    #[Test]
    public function returns_null_bootstrap_code_for_non_laravel(): void
    {
        mkdir($this->tempDir, 0777, true);

        $bootstrap = new LaravelBootstrap($this->tempDir);
        $code = $bootstrap->getBootstrapCode();

        $this->assertNull($code);
    }

    #[Test]
    public function bootstrap_code_includes_autoloader(): void
    {
        $this->createLaravelStructure();
        mkdir($this->tempDir . '/vendor', 0777, true);
        file_put_contents($this->tempDir . '/vendor/autoload.php', '<?php // autoload');

        $bootstrap = new LaravelBootstrap($this->tempDir);
        $code = $bootstrap->getBootstrapCode();

        $this->assertStringContainsString('vendor/autoload.php', $code);
    }

    private function createLaravelStructure(): void
    {
        mkdir($this->tempDir . '/bootstrap', 0777, true);
        file_put_contents($this->tempDir . '/bootstrap/app.php', '<?php return new stdClass();');
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
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
