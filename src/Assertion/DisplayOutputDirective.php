<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Assertion;

final readonly class DisplayOutputDirective
{
    public function __construct(
        public int $markdownLine,
        public ?int $lines = null,
        public ?int $tail = null,
    ) {}
}
