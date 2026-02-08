<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\CodeBlock;

final readonly class Attributes
{
    /**
     * @param  array<string>  $bootstraps
     */
    public function __construct(
        public ?Attribute $attribute = null,
        public ?string $throwsClass = null,
        public ?string $throwsMessage = null,
        public ?string $group = null,
        public array $bootstraps = [],
    ) {}

    public function isIgnore(): bool
    {
        return $this->attribute === Attribute::Ignore;
    }

    public function isNoRun(): bool
    {
        return $this->attribute === Attribute::NoRun;
    }

    public function isThrows(): bool
    {
        return $this->attribute === Attribute::Throws;
    }

    public function isParseError(): bool
    {
        return $this->attribute === Attribute::ParseError;
    }

    public function isSetup(): bool
    {
        return $this->attribute === Attribute::Setup;
    }

    public function isTeardown(): bool
    {
        return $this->attribute === Attribute::Teardown;
    }

    public function hasGroup(): bool
    {
        return $this->group !== null;
    }

    public function hasBootstraps(): bool
    {
        return $this->bootstraps !== [];
    }
}
