<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Parser;

use TestFlowLabs\DocTest\CodeBlock\Attribute;
use TestFlowLabs\DocTest\CodeBlock\Attributes;

final readonly class AttributeParser
{
    private const string THROWS_PATTERN = '/throws\(([^,)]+)(?:,\s*"([^"]*)")?\)/';

    private const string GROUP_PATTERN = '/group="([^"]+)"/';

    public function parse(string $infoString): Attributes
    {
        // Strip language identifier and Shiki metadata
        $tokens = $this->extractTokens($infoString);

        $attribute     = null;
        $throwsClass   = null;
        $throwsMessage = null;
        $group         = null;

        // Check for group
        if (preg_match(self::GROUP_PATTERN, $infoString, $groupMatch) === 1) {
            $group = $groupMatch[1];
        }

        // Check for throws with params
        if (preg_match(self::THROWS_PATTERN, $infoString, $throwsMatch) === 1) {
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
