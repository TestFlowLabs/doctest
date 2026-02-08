<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Parser;

use TestFlowLabs\DocTest\CodeBlock\Attribute;
use TestFlowLabs\DocTest\CodeBlock\Attributes;

final readonly class AttributeParser
{
    private const string THROWS_PATTERN = '/throws\(([^,)]+)(?:,\s*"([^"]*)")?\)/';

    private const string GROUP_PATTERN = '/group="([^"]+)"/';

    private const string BOOTSTRAP_PATTERN = '/bootstrap="([^"]+)"/';

    public function parse(string $infoString): Attributes
    {
        $tokens = $this->extractTokens($infoString);

        return $this->resolveAttributes($infoString, $tokens);
    }

    /**
     * @param  array<string>  $tokens
     */
    public function resolveAttributes(string $content, array $tokens = []): Attributes
    {
        $attribute     = null;
        $throwsClass   = null;
        $throwsMessage = null;
        $group         = null;
        $bootstraps    = [];

        // Check for group
        if (preg_match(self::GROUP_PATTERN, $content, $groupMatch) === 1) {
            $group = $groupMatch[1];
        }

        // Check for bootstrap profiles
        if (preg_match(self::BOOTSTRAP_PATTERN, $content, $bootstrapMatch) === 1) {
            $bootstraps = array_map(trim(...), explode(',', $bootstrapMatch[1]));
        }

        // Check for throws with params
        if (preg_match(self::THROWS_PATTERN, $content, $throwsMatch) === 1) {
            $attribute     = Attribute::Throws;
            $throwsClass   = trim($throwsMatch[1]);
            $throwsMessage = isset($throwsMatch[2]) && $throwsMatch[2] !== '' ? $throwsMatch[2] : null;
        } else {
            // Check for keyword attributes
            foreach ($tokens as $token) {
                $found = Attribute::tryFrom($token);
                if ($found !== null) {
                    $attribute = $found;
                    break;
                }
            }
        }

        return new Attributes(
            attribute: $attribute,
            throwsClass: $throwsClass,
            throwsMessage: $throwsMessage,
            group: $group,
            bootstraps: $bootstraps,
        );
    }

    /**
     * @return array<string>
     */
    private function extractTokens(string $infoString): array
    {
        // Remove Shiki line highlight {1,4-6}
        $cleaned = preg_replace('/\{[\d,\s-]+\}/', '', $infoString) ?? $infoString;

        // Split into tokens, skip the language identifier (first token)
        $parts = preg_split('/\s+/', trim($cleaned));

        if ($parts === false || count($parts) <= 1) {
            return [];
        }

        return array_slice($parts, 1);
    }
}
