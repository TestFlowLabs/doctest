<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Laravel;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Laravel\DatabaseSetup;

final class DatabaseSetupTest extends TestCase
{
    #[Test]
    public function generates_sqlite_memory_setup_code(): void
    {
        $setup = new DatabaseSetup(driver: 'sqlite', database: ':memory:');
        $code = $setup->getSetupCode();

        $this->assertStringContainsString(':memory:', $code);
        $this->assertStringContainsString('sqlite', $code);
    }

    #[Test]
    public function generates_migration_refresh_code(): void
    {
        $setup = new DatabaseSetup(driver: 'sqlite', database: ':memory:', runMigrations: true);
        $code = $setup->getSetupCode();

        $this->assertStringContainsString('migrate', $code);
    }

    #[Test]
    public function generates_teardown_code(): void
    {
        $setup = new DatabaseSetup(driver: 'sqlite', database: ':memory:');
        $code = $setup->getTeardownCode();

        $this->assertNotNull($code);
        $this->assertNotEmpty($code);
    }

    #[Test]
    public function no_migration_when_disabled(): void
    {
        $setup = new DatabaseSetup(driver: 'sqlite', database: ':memory:', runMigrations: false);
        $code = $setup->getSetupCode();

        $this->assertStringNotContainsString('migrate', $code);
    }

    #[Test]
    public function supports_custom_connection_name(): void
    {
        $setup = new DatabaseSetup(driver: 'sqlite', database: ':memory:', connection: 'testing');
        $code = $setup->getSetupCode();

        $this->assertStringContainsString('testing', $code);
    }
}
