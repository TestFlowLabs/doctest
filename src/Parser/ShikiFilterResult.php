<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Parser;

final readonly class ShikiFilterResult
{
    public function __construct(
        public string $code,
        public string $infoString,
    ) {}
}
