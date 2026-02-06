<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Assertion;

final readonly class HtmlCommentAssertionParser
{
    /**
     * @return array<Assertion>
     */
    public function parse(string $html): array
    {
        $html = trim($html);

        if (!str_starts_with($html, '<!--')) {
            return [];
        }

        // <!-- doctest-contains: value -->
        if (preg_match('/^<!--\s*doctest-contains:\s*(.+?)\s*-->$/s', $html, $matches)) {
            return [new OutputContainsAssertion(trim($matches[1]), 0)];
        }

        // <!-- doctest-matches: /pattern/ -->
        if (preg_match('/^<!--\s*doctest-matches:\s*(.+?)\s*-->$/s', $html, $matches)) {
            return [new OutputMatchesAssertion(trim($matches[1]), 0)];
        }

        // <!-- doctest-json: {"key":"value"} -->
        if (preg_match('/^<!--\s*doctest-json:\s*(.+?)\s*-->$/s', $html, $matches)) {
            return [new OutputJsonAssertion(trim($matches[1]), 0)];
        }

        // <!-- doctest-expect: expression -->
        if (preg_match('/^<!--\s*doctest-expect:\s*(.+?)\s*-->$/s', $html, $matches)) {
            return [new ExpectAssertion(trim($matches[1]), 0)];
        }

        // <!-- doctest: value --> (output)
        if (preg_match('/^<!--\s*doctest:\s*(.+?)\s*-->$/s', $html, $matches)) {
            return [new OutputAssertion(trim($matches[1]), 0)];
        }

        return [];
    }
}
