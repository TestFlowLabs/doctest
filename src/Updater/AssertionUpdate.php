<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Updater;

final readonly class AssertionUpdate
{
    public function __construct(
        public int $markdownLine,
        public string $type,
        public string $assertionType,
        public string $oldValue,
        public string $newValue,
    ) {}
}
