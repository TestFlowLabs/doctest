<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Laravel;

final readonly class LaravelBootstrap
{
    public function __construct(
        private string $basePath,
    ) {}

    public function isLaravelProject(): bool
    {
        return file_exists($this->basePath . '/bootstrap/app.php');
    }

    public function getBootstrapCode(): ?string
    {
        if (! $this->isLaravelProject()) {
            return null;
        }

        $lines = [];

        $autoloadPath = $this->basePath . '/vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            $lines[] = "require_once '" . addslashes($autoloadPath) . "';";
        }

        $bootstrapPath = $this->basePath . '/bootstrap/app.php';
        $lines[] = "\$app = require_once '" . addslashes($bootstrapPath) . "';";
        $lines[] = '$kernel = $app->make(\\Illuminate\\Contracts\\Console\\Kernel::class);';
        $lines[] = '$kernel->bootstrap();';

        return implode("\n", $lines);
    }

    /**
     * @param array<string> $providers
     */
    public function getProviderRegistrationCode(array $providers): string
    {
        if ($providers === []) {
            return '';
        }

        $lines = [];

        foreach ($providers as $provider) {
            $lines[] = '$app->register(\\' . ltrim($provider, '\\') . '::class);';
        }

        return implode("\n", $lines);
    }
}
