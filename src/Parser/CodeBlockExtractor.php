<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Parser;

use League\CommonMark\Node\NodeIterator;
use League\CommonMark\Node\Block\Document;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\Assertion\HtmlCommentAssertionParser;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;

final readonly class CodeBlockExtractor
{
    private ShikiFilter $shikiFilter;
    private AttributeParser $attributeParser;
    private AssertionParser $assertionParser;
    private HtmlCommentAssertionParser $htmlCommentParser;
    private HtmlCommentAttributeParser $htmlCommentAttributeParser;

    public function __construct()
    {
        $this->shikiFilter                = new ShikiFilter();
        $this->attributeParser            = new AttributeParser();
        $this->assertionParser            = new AssertionParser();
        $this->htmlCommentParser          = new HtmlCommentAssertionParser();
        $this->htmlCommentAttributeParser = new HtmlCommentAttributeParser();
    }

    /**
     * @return array<CodeBlock>
     */
    public function extract(Document $document, string $filePath): array
    {
        $blocks = [];

        /** @var array<HtmlBlock> $pendingHtmlBlocks */
        $pendingHtmlBlocks = [];

        foreach ($document->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            // Track HTML blocks as potential pre-block attribute comments
            if ($node instanceof HtmlBlock) {
                $pendingHtmlBlocks[] = $node;

                continue;
            }

            if (!$node instanceof FencedCode) {
                $pendingHtmlBlocks = [];

                continue;
            }

            $infoString = $node->getInfo() ?? '';

            if (!$this->isPhpBlock($infoString)) {
                $pendingHtmlBlocks = [];

                continue;
            }

            $rawCode = $node->getLiteral();

            // Delegate Shiki filtering
            $shikiResult = $this->shikiFilter->filter($rawCode, $infoString);

            // Parse attributes from cleaned info string
            $attributes = $this->attributeParser->parse($shikiResult->infoString);

            // Check preceding HTML blocks for doctest-attr comments
            $attributes        = $this->mergeHtmlCommentAttributes($attributes, $pendingHtmlBlocks);
            $pendingHtmlBlocks = [];

            // Strip opening <?php tag
            $code = $this->stripPhpTag($shikiResult->code);

            // Parse assertions from code comments
            $assertionResult = $this->assertionParser->parse($code);

            // Check for HTML comment assertions after the code block
            $htmlAssertions = [];
            $nextNode       = $node->next();
            while ($nextNode instanceof HtmlBlock) {
                $htmlAssertions = array_merge(
                    $htmlAssertions,
                    $this->htmlCommentParser->parse($nextNode->getLiteral()),
                );
                $nextNode = $nextNode->next();
            }

            $allAssertions = $htmlAssertions;

            $blocks[] = new CodeBlock(
                file: $filePath,
                startLine: $node->getStartLine() ?? 0,
                rawCode: $rawCode,
                executableCode: $assertionResult->executableCode,
                attributes: $attributes,
                assertions: $allAssertions,
            );
        }

        return $blocks;
    }

    /**
     * @param  array<HtmlBlock>  $htmlBlocks
     */
    private function mergeHtmlCommentAttributes(Attributes $infoStringAttrs, array $htmlBlocks): Attributes
    {
        $foundCommentAttrs = null;

        foreach ($htmlBlocks as $htmlBlock) {
            $commentAttrs = $this->htmlCommentAttributeParser->parse($htmlBlock->getLiteral());

            if ($commentAttrs === null) {
                continue;
            }

            if ($foundCommentAttrs !== null) {
                throw new \RuntimeException(
                    'Multiple doctest-attr HTML comments found before code block. Use only one comment.'
                );
            }

            $foundCommentAttrs = $commentAttrs;
        }

        if ($foundCommentAttrs === null) {
            return $infoStringAttrs;
        }

        $this->detectConflicts($infoStringAttrs, $foundCommentAttrs);

        return new Attributes(
            attribute: $foundCommentAttrs->attribute ?? $infoStringAttrs->attribute,
            throwsClass: $foundCommentAttrs->throwsClass ?? $infoStringAttrs->throwsClass,
            throwsMessage: $foundCommentAttrs->throwsMessage ?? $infoStringAttrs->throwsMessage,
            group: $foundCommentAttrs->group ?? $infoStringAttrs->group,
            bootstraps: $foundCommentAttrs->bootstraps !== [] ? $foundCommentAttrs->bootstraps : $infoStringAttrs->bootstraps,
        );
    }

    private function detectConflicts(Attributes $infoString, Attributes $comment): void
    {
        if ($infoString->attribute !== null && $comment->attribute !== null) {
            throw new \RuntimeException(
                "Conflicting attribute: info string has '{$infoString->attribute->value}' but HTML comment has '{$comment->attribute->value}'. Use only one source."
            );
        }

        if ($infoString->group !== null && $comment->group !== null) {
            throw new \RuntimeException(
                "Conflicting group: info string has '{$infoString->group}' but HTML comment has '{$comment->group}'. Use only one source."
            );
        }

        if ($infoString->bootstraps !== [] && $comment->bootstraps !== []) {
            throw new \RuntimeException(
                'Conflicting bootstrap: both info string and HTML comment set bootstrap profiles. Use only one source.'
            );
        }
    }

    private function isPhpBlock(string $infoString): bool
    {
        if ($infoString === '') {
            return false;
        }

        $parts    = preg_split('/[\s{]/', $infoString);
        $language = is_array($parts) ? $parts[0] : '';

        return mb_strtolower($language) === 'php';
    }

    private function stripPhpTag(string $code): string
    {
        return (string) preg_replace('/^<\?php\s*\n?/', '', $code);
    }
}
