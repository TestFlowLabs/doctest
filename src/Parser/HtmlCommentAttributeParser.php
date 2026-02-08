<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Parser;

use TestFlowLabs\DocTest\CodeBlock\Attribute;
use TestFlowLabs\DocTest\CodeBlock\Attributes;

final readonly class HtmlCommentAttributeParser
{
    private const string COMMENT_PATTERN = '/^<!--\s*doctest-attr:\s*(.*?)\s*-->$/s';

    private const string THROWS_PATTERN = '/throws\(([^,)]+)(?:,\s*"([^"]*)")?\)/';

    private const string GROUP_PATTERN = '/group="([^"]+)"/';

    private const string BOOTSTRAP_PATTERN = '/bootstrap="([^"]+)"/';

    public function parse(string $html): ?Attributes
    {
        $html = trim($html);

        if (!str_starts_with($html, '<!--')) {
            return null;
        }

        if (preg_match(self::COMMENT_PATTERN, $html, $commentMatch) !== 1) {
            return null;
        }

        $content = trim($commentMatch[1]);

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
            $tokens = preg_split('/\s+/', $content);
            $tokens = is_array($tokens) ? $tokens : [];

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
}
