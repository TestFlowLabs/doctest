<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Laravel;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Laravel\LaravelBootstrap;

final class LaravelBootstrapTest extends TestCase
{
    #[Test]
    public function detects_laravel_when_bootstrap_app_exists(): void
    {
        $tempDir = sys_get_temp_dir() . '/doctest_laravel_' . uniqid();
        mkdir($tempDir . '/bootstrap', 0777, true);
        file_put_contents($tempDir . '/bootstrap/app.php', '<?php return new stdClass();');

        $bootstrap = new LaravelBootstrap($tempDir);

        $this->assertTrue($bootstrap->isLaravelProject());

        unlink($tempDir . '/bootstrap/app.php');
        rmdir($tempDir . '/bootstrap');
        rmdir($tempDir);
    }

    #[Test]
    public function detects_no_laravel_without_bootstrap(): void
    {
        $tempDir = sys_get_temp_dir() . '/doctest_nolaravel_' . uniqid();
        mkdir($tempDir, 0777, true);

        $bootstrap = new LaravelBootstrap($tempDir);

        $this->assertFalse($bootstrap->isLaravelProject());

        rmdir($tempDir);
    }

    #[Test]
    public function generates_bootstrap_code_for_laravel(): void
    {
        $tempDir = sys_get_temp_dir() . '/doctest_laravel_' . uniqid();
        mkdir($tempDir . '/bootstrap', 0777, true);
        file_put_contents($tempDir . '/bootstrap/app.php', '<?php return new stdClass();');

        $bootstrap = new LaravelBootstrap($tempDir);
        $code = $bootstrap->getBootstrapCode();

        $this->assertNotNull($code);
        $this->assertStringContainsString('bootstrap/app.php', $code);

        unlink($tempDir . '/bootstrap/app.php');
        rmdir($tempDir . '/bootstrap');
        rmdir($tempDir);
    }

    #[Test]
    public function returns_null_bootstrap_code_for_non_laravel(): void
    {
        $tempDir = sys_get_temp_dir() . '/doctest_nolaravel_' . uniqid();
        mkdir($tempDir, 0777, true);

        $bootstrap = new LaravelBootstrap($tempDir);
        $code = $bootstrap->getBootstrapCode();

        $this->assertNull($code);

        rmdir($tempDir);
    }

    #[Test]
    public function bootstrap_code_includes_autoloader(): void
    {
        $tempDir = sys_get_temp_dir() . '/doctest_laravel_' . uniqid();
        mkdir($tempDir . '/bootstrap', 0777, true);
        mkdir($tempDir . '/vendor', 0777, true);
        file_put_contents($tempDir . '/bootstrap/app.php', '<?php return new stdClass();');
        file_put_contents($tempDir . '/vendor/autoload.php', '<?php // autoload');

        $bootstrap = new LaravelBootstrap($tempDir);
        $code = $bootstrap->getBootstrapCode();

        $this->assertStringContainsString('vendor/autoload.php', $code);

        unlink($tempDir . '/vendor/autoload.php');
        unlink($tempDir . '/bootstrap/app.php');
        rmdir($tempDir . '/vendor');
        rmdir($tempDir . '/bootstrap');
        rmdir($tempDir);
    }
}
