<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Comparison;

final readonly class WildcardMatcher
{
    /** @var array<string, string> */
    private const array PATTERNS = [
        '{{any}}'      => '.+?',
        '{{int}}'      => '-?\d+',
        '{{float}}'    => '-?\d+\.?\d*',
        '{{uuid}}'     => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
        '{{datetime}}' => '\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[^\s]*',
        '{{date}}'     => '\d{4}-\d{2}-\d{2}',
        '{{time}}'     => '\d{2}:\d{2}:\d{2}',
        '{{...}}'      => '[\s\S]*?',
    ];

    public function matches(string $actual, string $pattern): bool
    {
        if (!str_contains($pattern, '{{')) {
            return $actual === $pattern;
        }

        $regex = $this->buildRegex($pattern);

        return preg_match($regex, $actual) === 1;
    }

    public function hasWildcards(string $pattern): bool
    {
        return array_any(array_keys(self::PATTERNS), fn ($placeholder) => str_contains($pattern, (string) $placeholder));
    }

    private function buildRegex(string $pattern): string
    {
        // Escape the pattern first
        $escaped = preg_quote($pattern, '/');

        // Replace escaped wildcards with their regex patterns
        foreach (self::PATTERNS as $placeholder => $regex) {
            $escapedPlaceholder = preg_quote($placeholder, '/');
            $escaped            = str_replace($escapedPlaceholder, $regex, $escaped);
        }

        return '/^'.$escaped.'$/s';
    }
}
