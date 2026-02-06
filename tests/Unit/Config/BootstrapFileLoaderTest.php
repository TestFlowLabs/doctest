<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Config;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\Config\BootstrapFileLoader;

final class BootstrapFileLoaderTest extends TestCase
{
    #[Test]
    public function generates_require_once_for_existing_file(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'doctest_bootstrap_');
        file_put_contents($tempFile, '<?php // bootstrap');

        $loader = new BootstrapFileLoader($tempFile);

        $this->assertStringContainsString('require_once', $loader->getBootstrapCode());
        $this->assertStringContainsString($tempFile, $loader->getBootstrapCode());

        unlink($tempFile);
    }

    #[Test]
    public function throws_exception_for_nonexistent_file(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Bootstrap file does not exist');

        new BootstrapFileLoader('/nonexistent/bootstrap.php');
    }

    #[Test]
    public function returns_null_when_no_path_configured(): void
    {
        $loader = new BootstrapFileLoader(null);

        $this->assertNull($loader->getBootstrapCode());
    }

    #[Test]
    public function resolves_absolute_path_in_generated_code(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'doctest_bootstrap_');
        file_put_contents($tempFile, '<?php // bootstrap');

        $loader = new BootstrapFileLoader($tempFile);
        $code   = $loader->getBootstrapCode();

        $this->assertStringContainsString(realpath($tempFile), $code);

        unlink($tempFile);
    }
}
