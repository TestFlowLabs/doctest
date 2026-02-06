<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Audit;

final readonly class AuditFinding
{
    public function __construct(
        public string $file,
        public int $line,
        public string $category,
        public string $match,
    ) {}
}
