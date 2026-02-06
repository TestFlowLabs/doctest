<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Laravel;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Laravel\LaravelBootstrap;

final class ServiceProviderRegistrationTest extends TestCase
{
    #[Test]
    public function generates_provider_registration_code(): void
    {
        $tempDir = sys_get_temp_dir() . '/doctest_laravel_' . uniqid();
        mkdir($tempDir . '/bootstrap', 0777, true);
        file_put_contents($tempDir . '/bootstrap/app.php', '<?php return new stdClass();');

        $bootstrap = new LaravelBootstrap($tempDir);
        $providers = ['App\\Providers\\CustomProvider', 'App\\Providers\\AnotherProvider'];
        $code = $bootstrap->getProviderRegistrationCode($providers);

        $this->assertStringContainsString('App\\Providers\\CustomProvider', $code);
        $this->assertStringContainsString('App\\Providers\\AnotherProvider', $code);
        $this->assertStringContainsString('register', $code);

        unlink($tempDir . '/bootstrap/app.php');
        rmdir($tempDir . '/bootstrap');
        rmdir($tempDir);
    }

    #[Test]
    public function empty_providers_returns_empty_string(): void
    {
        $tempDir = sys_get_temp_dir() . '/doctest_laravel_' . uniqid();
        mkdir($tempDir . '/bootstrap', 0777, true);
        file_put_contents($tempDir . '/bootstrap/app.php', '<?php return new stdClass();');

        $bootstrap = new LaravelBootstrap($tempDir);
        $code = $bootstrap->getProviderRegistrationCode([]);

        $this->assertSame('', $code);

        unlink($tempDir . '/bootstrap/app.php');
        rmdir($tempDir . '/bootstrap');
        rmdir($tempDir);
    }

    #[Test]
    public function provider_code_calls_register_on_app(): void
    {
        $tempDir = sys_get_temp_dir() . '/doctest_laravel_' . uniqid();
        mkdir($tempDir . '/bootstrap', 0777, true);
        file_put_contents($tempDir . '/bootstrap/app.php', '<?php return new stdClass();');

        $bootstrap = new LaravelBootstrap($tempDir);
        $code = $bootstrap->getProviderRegistrationCode(['App\\MyProvider']);

        $this->assertStringContainsString('$app->register', $code);

        unlink($tempDir . '/bootstrap/app.php');
        rmdir($tempDir . '/bootstrap');
        rmdir($tempDir);
    }
}
