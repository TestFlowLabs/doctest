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
        $lines    = explode("\n", $code);
        $filtered = [];

        foreach ($lines as $line) {
            // 1. Remove lines with [!code --] entirely (changes execution semantics)
            if (str_contains($line, '// [!code --]')) {
                continue;
            }

            // 2. Remove block delimiter lines entirely
            if (str_contains($line, '// [!code hide:start]') || str_contains($line, '// [!code hide:end]')) {
                continue;
            }

            // 3. Strip any remaining // [!code xxx] marker (++, hide, highlight, focus, etc.)
            $line = (string) preg_replace('/\s*\/\/\s*\[!code [^\]]+\]/', '', $line);

            $filtered[] = $line;
        }

        return new ShikiFilterResult(
            code: implode("\n", $filtered),
            infoString: $cleanedInfoString,
        );
    }
}
