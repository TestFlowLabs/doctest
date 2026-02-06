<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Parser;

use League\CommonMark\Node\NodeIterator;
use League\CommonMark\Node\Block\Document;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
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

    public function __construct()
    {
        $this->shikiFilter       = new ShikiFilter();
        $this->attributeParser   = new AttributeParser();
        $this->assertionParser   = new AssertionParser();
        $this->htmlCommentParser = new HtmlCommentAssertionParser();
    }

    /**
     * @return array<CodeBlock>
     */
    public function extract(Document $document, string $filePath): array
    {
        $blocks = [];

        foreach ($document->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            if (!$node instanceof FencedCode) {
                continue;
            }

            $infoString = $node->getInfo() ?? '';

            if (!$this->isPhpBlock($infoString)) {
                continue;
            }

            $rawCode = $node->getLiteral();

            // Delegate Shiki filtering
            $shikiResult = $this->shikiFilter->filter($rawCode, $infoString);

            // Parse attributes from cleaned info string
            $attributes = $this->attributeParser->parse($shikiResult->infoString);

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
