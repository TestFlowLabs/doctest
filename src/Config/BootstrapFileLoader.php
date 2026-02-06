<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Config;

use InvalidArgumentException;

final readonly class BootstrapFileLoader
{
    private ?string $resolvedPath;

    public function __construct(?string $path)
    {
        if ($path === null) {
            $this->resolvedPath = null;

            return;
        }

        $realPath = realpath($path);

        if ($realPath === false) {
            throw new InvalidArgumentException("Bootstrap file does not exist: {$path}");
        }

        $this->resolvedPath = $realPath;
    }

    public function getBootstrapCode(): ?string
    {
        if ($this->resolvedPath === null) {
            return null;
        }

        return "require_once '" . addslashes($this->resolvedPath) . "';";
    }
}
