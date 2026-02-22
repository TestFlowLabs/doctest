<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Assertion;

final readonly class HtmlCommentAssertionParser
{
    /**
     * @return array<Assertion>
     */
    public function parse(string $html, int $markdownLine = 0): array
    {
        $html = trim($html);

        if (!str_starts_with($html, '<!--')) {
            return [];
        }

        // <!-- doctest-contains: value -->
        if (preg_match('/^<!--\s*doctest-contains:\s*(.+?)\s*-->$/s', $html, $matches)) {
            return [new OutputContainsAssertion(trim($matches[1]), $markdownLine)];
        }

        // <!-- doctest-matches: /pattern/ -->
        if (preg_match('/^<!--\s*doctest-matches:\s*(.+?)\s*-->$/s', $html, $matches)) {
            return [new OutputMatchesAssertion(trim($matches[1]), $markdownLine)];
        }

        // <!-- doctest-json: {"key":"value"} -->
        if (preg_match('/^<!--\s*doctest-json:\s*(.+?)\s*-->$/s', $html, $matches)) {
            return [new OutputJsonAssertion(trim($matches[1]), $markdownLine)];
        }

        // <!-- doctest-expect: expression -->
        if (preg_match('/^<!--\s*doctest-expect:\s*(.+?)\s*-->$/s', $html, $matches)) {
            return [new ExpectAssertion(trim($matches[1]), $markdownLine)];
        }

        // <!-- doctest: value --> (output)
        if (preg_match('/^<!--\s*doctest:\s*(.+?)\s*-->$/s', $html, $matches)) {
            return [new OutputAssertion(trim($matches[1]), $markdownLine)];
        }

        return [];
    }

    public function parseDirective(string $html, int $markdownLine = 0): ?DisplayOutputDirective
    {
        $html = trim($html);

        if (!str_starts_with($html, '<!--')) {
            return null;
        }

        // <!-- doctest-output --> or <!-- doctest-output: options -->
        if (preg_match('/^<!--\s*doctest-output(?::\s*(.+?))?\s*-->$/s', $html, $matches)) {
            $options = isset($matches[1]) ? trim($matches[1]) : '';

            $lines = null;
            $tail  = null;

            if ($options !== '') {
                if (preg_match('/lines=(\d+)/', $options, $m)) {
                    $lines = (int) $m[1];
                }
                if (preg_match('/tail=(\d+)/', $options, $m)) {
                    $tail = (int) $m[1];
                }
            }

            return new DisplayOutputDirective(
                markdownLine: $markdownLine,
                lines: $lines,
                tail: $tail,
            );
        }

        return null;
    }
}
