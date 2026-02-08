<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Config;

final readonly class BootstrapResolver
{
    /** @var array<string, string> profile name => absolute path */
    private array $profiles;

    public function __construct(
        string $bootstrapsDir,
        private ?string $globalBootstrap = null,
    ) {
        $this->profiles = $this->discoverProfiles($bootstrapsDir);
    }

    /**
     * @param  array<string>  $profileNames
     */
    public function resolve(array $profileNames): string
    {
        $lines = [];

        if ($this->globalBootstrap !== null) {
            $lines[] = $this->globalBootstrap;
        }

        foreach ($profileNames as $name) {
            if (!isset($this->profiles[$name])) {
                $available = implode(', ', array_keys($this->profiles));
                $hint      = $available !== '' ? " Available profiles: {$available}" : '';

                throw new \RuntimeException("Unknown bootstrap profile \"{$name}\".{$hint}");
            }

            $lines[] = "require_once '".addslashes($this->profiles[$name])."';";
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<string>
     */
    public function availableProfiles(): array
    {
        $names = array_keys($this->profiles);
        sort($names);

        return $names;
    }

    /**
     * @return array<string, string>
     */
    private function discoverProfiles(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $profiles = [];
        $files    = glob($dir.'/*.php') ?: [];

        foreach ($files as $file) {
            $name            = pathinfo($file, PATHINFO_FILENAME);
            $resolved        = realpath($file);
            $profiles[$name] = $resolved !== false ? $resolved : $file;
        }

        return $profiles;
    }
}
