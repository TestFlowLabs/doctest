<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Assertion;

interface Assertion
{
    public function type(): string;

    public function line(): int;
}
