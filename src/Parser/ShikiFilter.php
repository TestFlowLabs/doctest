<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Parser;

final readonly class ShikiFilter
{
    public function filter(string $code, string $infoString): ShikiFilterResult
    {
        // Strip line highlight notation {1,4-6} from info string
        $cleanedInfoString = preg_replace('/\{[\d,\s-]+\}/', '', $infoString) ?? $infoString;

        // Process code lines
        $lines = explode("\n", $code);
        $filtered = [];

        foreach ($lines as $line) {
            // Remove lines with [!code --] entirely
            if (str_contains($line, '// [!code --]')) {
                continue;
            }

            // Strip [!code ++] marker but keep the code
            if (str_contains($line, '// [!code ++]')) {
                $line = (string) preg_replace('/\s*\/\/\s*\[!code \+\+\]/', '', $line);
            }

            $filtered[] = $line;
        }

        return new ShikiFilterResult(
            code: implode("\n", $filtered),
            infoString: $cleanedInfoString,
        );
    }
}
