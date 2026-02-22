<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Updater;

final class MarkdownRewriter
{
    /**
     * @param  array<AssertionUpdate>  $updates
     *
     * @return int Number of updates applied
     */
    public function rewrite(string $filePath, array $updates): int
    {
        if ($updates === []) {
            return 0;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            return 0;
        }

        // Sort updates by markdownLine DESC (bottom-up to preserve line numbers)
        usort($updates, static fn (AssertionUpdate $a, AssertionUpdate $b) => $b->markdownLine <=> $a->markdownLine);

        $applied = 0;

        foreach ($updates as $update) {
            $index = $update->markdownLine - 1; // Convert 1-based to 0-based

            if ($index < 0 || $index >= count($lines)) {
                continue;
            }

            if ($update->type === 'html_comment') {
                $result = $this->rewriteHtmlComment($lines, $index, $update);
                if ($result !== null) {
                    $lines = $result;
                    $applied++;
                }
            } elseif ($update->type === 'result_comment') {
                $lines = $this->rewriteResultComment($lines, $index, $update);
                $applied++;
            }
        }

        if ($applied > 0) {
            file_put_contents($filePath, implode("\n", $lines)."\n");
        }

        return $applied;
    }

    /**
     * @param  array<string>  $lines
     *
     * @return array<string>|null Null if comment is malformed
     */
    private function rewriteHtmlComment(array $lines, int $index, AssertionUpdate $update): ?array
    {
        // Determine the range of the existing HTML comment
        $endIndex = $index;
        if (!str_contains($lines[$index], '-->')) {
            // Multi-line comment — find closing -->
            $lineCount = count($lines);
            $found     = false;
            for ($i = $index + 1; $i < $lineCount; $i++) {
                if (str_contains($lines[$i], '-->')) {
                    $endIndex = $i;
                    $found    = true;

                    break;
                }
            }

            if (!$found) {
                return null;
            }
        }

        // Build replacement
        $commentTag = $this->commentTagForType($update->assertionType);
        if (str_contains($update->newValue, "\n")) {
            // Multi-line replacement
            $replacement = [
                "<!-- {$commentTag}:",
                ...explode("\n", $update->newValue),
                '-->',
            ];
        } else {
            // Single-line replacement
            $replacement = ["<!-- {$commentTag}: {$update->newValue} -->"];
        }

        array_splice($lines, $index, $endIndex - $index + 1, $replacement);

        return $lines;
    }

    /**
     * @param  array<string>  $lines
     *
     * @return array<string>
     */
    private function rewriteResultComment(array $lines, int $index, AssertionUpdate $update): array
    {
        $line = $lines[$index];

        $result = preg_replace(
            '/\/\/\s*=>\s*.+$/',
            '// => '.$update->newValue,
            $line,
        );
        $lines[$index] = $result ?? $line;

        return $lines;
    }

    /**
     * @param  array<string>  $lines
     *
     * @return array<string>
     */
    public function rewriteDisplayBlock(array $lines, int $contentStartLine, int $contentEndLine, string $newContent): array
    {
        $startIndex = $contentStartLine - 1;
        $endIndex   = $contentEndLine - 1;

        if ($startIndex < 0 || $endIndex < $startIndex || $endIndex >= count($lines)) {
            return $lines;
        }

        $replacement = explode("\n", $newContent);
        array_splice($lines, $startIndex, $endIndex - $startIndex + 1, $replacement);

        return $lines;
    }

    /**
     * Rewrites all display blocks in a file in a single pass (bottom-up).
     *
     * @param  array<array{block: \TestFlowLabs\DocTest\CodeBlock\DisplayOutputBlock, output: string}>  $displayUpdates
     */
    public function rewriteDisplayBlocks(string $filePath, array $displayUpdates): void
    {
        if ($displayUpdates === []) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            return;
        }

        // Sort bottom-up by contentStartLine DESC to preserve line numbers
        usort($displayUpdates, static fn (array $a, array $b) => $b['block']->contentStartLine <=> $a['block']->contentStartLine);

        foreach ($displayUpdates as $du) {
            $lines = $this->rewriteDisplayBlock($lines, $du['block']->contentStartLine, $du['block']->contentEndLine, $du['output']);
        }

        file_put_contents($filePath, implode("\n", $lines)."\n");
    }

    private function commentTagForType(string $assertionType): string
    {
        return match ($assertionType) {
            'output_json' => 'doctest-json',
            default       => 'doctest',
        };
    }
}
