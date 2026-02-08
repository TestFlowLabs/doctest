<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Parser;

use TestFlowLabs\DocTest\CodeBlock\Attributes;

final readonly class HtmlCommentAttributeParser
{
    private const string COMMENT_PATTERN = '/^<!--\s*doctest-attr:\s*(.*?)\s*-->$/s';

    public function __construct(
        private AttributeParser $attributeParser = new AttributeParser(),
    ) {}

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

        $tokens = preg_split('/\s+/', $content);
        $tokens = is_array($tokens) ? $tokens : [];

        return $this->attributeParser->resolveAttributes($content, $tokens);
    }
}
