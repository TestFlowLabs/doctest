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

        if (! str_starts_with($html, '<!--')) {
            return [];
        }

        // Single-line: <!-- doctest: value -->
        if (preg_match('/^<!--\s*doctest:\s*(.+?)\s*-->$/s', $html, $matches)) {
            return [new OutputAssertion(trim($matches[1]), 0)];
        }

        return [];
    }
}
