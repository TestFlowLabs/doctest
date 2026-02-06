<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Parser;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\CodeBlock\Attribute;
use TestFlowLabs\DocTest\Parser\AttributeParser;

final class AttributeParserTest extends TestCase
{
    private AttributeParser $parser;

    protected function setUp(): void
    {
        $this->parser = new AttributeParser();
    }

    #[Test]
    public function parses_empty_info_string(): void
    {
        $attributes = $this->parser->parse('php');

        $this->assertNull($attributes->attribute);
        $this->assertNull($attributes->group);
    }

    #[Test]
    public function parses_ignore(): void
    {
        $attributes = $this->parser->parse('php ignore');

        $this->assertSame(Attribute::Ignore, $attributes->attribute);
    }

    #[Test]
    public function parses_no_run(): void
    {
        $attributes = $this->parser->parse('php no_run');

        $this->assertSame(Attribute::NoRun, $attributes->attribute);
    }

    #[Test]
    public function parses_bare_throws(): void
    {
        $attributes = $this->parser->parse('php throws');

        $this->assertSame(Attribute::Throws, $attributes->attribute);
        $this->assertNull($attributes->throwsClass);
        $this->assertNull($attributes->throwsMessage);
    }

    #[Test]
    public function parses_throws_with_class(): void
    {
        $attributes = $this->parser->parse('php throws(InvalidArgumentException)');

        $this->assertSame(Attribute::Throws, $attributes->attribute);
        $this->assertSame('InvalidArgumentException', $attributes->throwsClass);
        $this->assertNull($attributes->throwsMessage);
    }

    #[Test]
    public function parses_throws_with_class_and_message(): void
    {
        $attributes = $this->parser->parse('php throws(InvalidArgumentException, "Bad input")');

        $this->assertSame(Attribute::Throws, $attributes->attribute);
        $this->assertSame('InvalidArgumentException', $attributes->throwsClass);
        $this->assertSame('Bad input', $attributes->throwsMessage);
    }

    #[Test]
    public function parses_parse_error(): void
    {
        $attributes = $this->parser->parse('php parse_error');

        $this->assertSame(Attribute::ParseError, $attributes->attribute);
    }

    #[Test]
    public function parses_setup(): void
    {
        $attributes = $this->parser->parse('php setup');

        $this->assertSame(Attribute::Setup, $attributes->attribute);
    }

    #[Test]
    public function parses_teardown(): void
    {
        $attributes = $this->parser->parse('php teardown');

        $this->assertSame(Attribute::Teardown, $attributes->attribute);
    }

    #[Test]
    public function parses_group(): void
    {
        $attributes = $this->parser->parse('php group="order-flow"');

        $this->assertSame('order-flow', $attributes->group);
    }

    #[Test]
    public function ignores_unknown_attributes(): void
    {
        $attributes = $this->parser->parse('php unknown_thing');

        $this->assertNull($attributes->attribute);
    }

    #[Test]
    public function group_combined_with_attribute(): void
    {
        $attributes = $this->parser->parse('php group="test" throws');

        $this->assertSame(Attribute::Throws, $attributes->attribute);
        $this->assertSame('test', $attributes->group);
    }
}
