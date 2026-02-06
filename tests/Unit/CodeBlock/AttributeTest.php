<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\CodeBlock;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\CodeBlock\Attribute;

final class AttributeTest extends TestCase
{
    #[Test]
    public function from_valid_string_returns_case(): void
    {
        $this->assertSame(Attribute::Ignore, Attribute::from('ignore'));
        $this->assertSame(Attribute::NoRun, Attribute::from('no_run'));
        $this->assertSame(Attribute::Throws, Attribute::from('throws'));
        $this->assertSame(Attribute::ParseError, Attribute::from('parse_error'));
        $this->assertSame(Attribute::Setup, Attribute::from('setup'));
        $this->assertSame(Attribute::Teardown, Attribute::from('teardown'));
    }

    #[Test]
    public function try_from_invalid_string_returns_null(): void
    {
        $this->assertNull(Attribute::tryFrom('invalid'));
        $this->assertNull(Attribute::tryFrom(''));
    }
}
