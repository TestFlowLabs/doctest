<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Laravel;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Laravel\LaravelBootstrap;

final class ServiceProviderRegistrationTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/doctest_laravel_' . bin2hex(random_bytes(8));
        mkdir($this->tempDir . '/bootstrap', 0777, true);
        file_put_contents($this->tempDir . '/bootstrap/app.php', '<?php return new stdClass();');
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    #[Test]
    public function generates_provider_registration_code(): void
    {
        $bootstrap = new LaravelBootstrap($this->tempDir);
        $providers = ['App\\Providers\\CustomProvider', 'App\\Providers\\AnotherProvider'];
        $code = $bootstrap->getProviderRegistrationCode($providers);

        $this->assertStringContainsString('App\\Providers\\CustomProvider', $code);
        $this->assertStringContainsString('App\\Providers\\AnotherProvider', $code);
        $this->assertStringContainsString('register', $code);
    }

    #[Test]
    public function empty_providers_returns_empty_string(): void
    {
        $bootstrap = new LaravelBootstrap($this->tempDir);
        $code = $bootstrap->getProviderRegistrationCode([]);

        $this->assertSame('', $code);
    }

    #[Test]
    public function provider_code_calls_register_on_app(): void
    {
        $bootstrap = new LaravelBootstrap($this->tempDir);
        $code = $bootstrap->getProviderRegistrationCode(['App\\MyProvider']);

        $this->assertStringContainsString('$app->register', $code);
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
