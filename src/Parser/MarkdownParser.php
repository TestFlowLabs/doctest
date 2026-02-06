<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Parser;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Parser\MarkdownParser as LeagueMarkdownParser;

final readonly class MarkdownParser
{
    private LeagueMarkdownParser $parser;

    public function __construct()
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());

        $this->parser = new LeagueMarkdownParser($environment);
    }

    public function parse(string $markdown): Document
    {
        return $this->parser->parse($markdown);
    }
}
