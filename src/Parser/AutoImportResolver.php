<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Parser;

final readonly class AutoImportResolver
{
    /** @var array<string> */
    private array $resolvedImports;

    /**
     * @param array<string>              $imports
     * @param array<string, string>|null $classMap
     */
    public function __construct(
        array $imports,
        ?array $classMap = null,
    ) {
        $this->resolvedImports = $this->resolve($imports, $classMap ?? []);
    }

    public function generateUseStatements(?string $blockCode = null): string
    {
        if ($this->resolvedImports === []) {
            return '';
        }

        $existingImports = $blockCode !== null ? $this->extractExistingImports($blockCode) : [];

        $lines = [];

        foreach ($this->resolvedImports as $import) {
            if (! in_array($import, $existingImports, true)) {
                $lines[] = 'use ' . $import . ';';
            }
        }

        if ($lines === []) {
            return '';
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<string>         $imports
     * @param array<string, string> $classMap
     *
     * @return array<string>
     */
    private function resolve(array $imports, array $classMap): array
    {
        $resolved = [];

        foreach ($imports as $import) {
            if (str_ends_with($import, '\\*')) {
                $prefix = substr($import, 0, -1);
                foreach (array_keys($classMap) as $className) {
                    if (str_starts_with($className, $prefix)) {
                        $resolved[] = $className;
                    }
                }
            } else {
                $resolved[] = $import;
            }
        }

        return array_values(array_unique($resolved));
    }

    /**
     * @return array<string>
     */
    private function extractExistingImports(string $code): array
    {
        $imports = [];

        if (preg_match_all('/^use\s+([\w\\\\]+);/m', $code, $matches)) {
            $imports = $matches[1];
        }

        return $imports;
    }
}
