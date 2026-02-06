<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Laravel;

final readonly class DatabaseSetup
{
    public function __construct(
        private string $driver = 'sqlite',
        private string $database = ':memory:',
        private bool $runMigrations = false,
        private string $connection = 'doctest',
    ) {}

    public function getSetupCode(): string
    {
        $lines = [];

        $lines[] = "config(['database.connections.{$this->connection}' => [";
        $lines[] = "    'driver' => '{$this->driver}',";
        $lines[] = "    'database' => '{$this->database}',";
        $lines[] = "    'prefix' => '',";
        $lines[] = ']]);';
        $lines[] = "config(['database.default' => '{$this->connection}']);";

        if ($this->runMigrations) {
            $lines[] = "\\Illuminate\\Support\\Facades\\Artisan::call('migrate:fresh');";
        }

        return implode("\n", $lines);
    }

    public function getTeardownCode(): string
    {
        $lines = [];
        $lines[] = "\\Illuminate\\Support\\Facades\\DB::disconnect('{$this->connection}');";

        return implode("\n", $lines);
    }
}
