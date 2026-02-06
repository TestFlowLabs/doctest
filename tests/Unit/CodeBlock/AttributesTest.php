<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\CodeBlock;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\CodeBlock\Attribute;
use TestFlowLabs\DocTest\CodeBlock\Attributes;

final class AttributesTest extends TestCase
{
    #[Test]
    public function construction_with_defaults(): void
    {
        $attributes = new Attributes();

        $this->assertNull($attributes->attribute);
        $this->assertNull($attributes->throwsClass);
        $this->assertNull($attributes->throwsMessage);
        $this->assertNull($attributes->group);
    }

    #[Test]
    public function is_ignore_returns_true_for_ignore_attribute(): void
    {
        $attributes = new Attributes(attribute: Attribute::Ignore);

        $this->assertTrue($attributes->isIgnore());
    }

    #[Test]
    public function is_ignore_returns_false_for_other_attribute(): void
    {
        $attributes = new Attributes(attribute: Attribute::Throws);

        $this->assertFalse($attributes->isIgnore());
    }

    #[Test]
    public function is_throws_returns_true_for_throws_attribute(): void
    {
        $attributes = new Attributes(attribute: Attribute::Throws);

        $this->assertTrue($attributes->isThrows());
    }

    #[Test]
    public function throws_with_class_and_message(): void
    {
        $attributes = new Attributes(
            attribute: Attribute::Throws,
            throwsClass: 'InvalidArgumentException',
            throwsMessage: 'Bad input',
        );

        $this->assertTrue($attributes->isThrows());
        $this->assertSame('InvalidArgumentException', $attributes->throwsClass);
        $this->assertSame('Bad input', $attributes->throwsMessage);
    }

    #[Test]
    public function has_group_returns_true_when_group_set(): void
    {
        $attributes = new Attributes(group: 'order-flow');

        $this->assertTrue($attributes->hasGroup());
        $this->assertSame('order-flow', $attributes->group);
    }

    #[Test]
    public function has_group_returns_false_when_no_group(): void
    {
        $attributes = new Attributes();

        $this->assertFalse($attributes->hasGroup());
    }

    #[Test]
    public function is_setup_returns_true_for_setup_attribute(): void
    {
        $attributes = new Attributes(attribute: Attribute::Setup);

        $this->assertTrue($attributes->isSetup());
    }

    #[Test]
    public function is_teardown_returns_true_for_teardown_attribute(): void
    {
        $attributes = new Attributes(attribute: Attribute::Teardown);

        $this->assertTrue($attributes->isTeardown());
    }

    #[Test]
    public function is_no_run_returns_true_for_no_run_attribute(): void
    {
        $attributes = new Attributes(attribute: Attribute::NoRun);

        $this->assertTrue($attributes->isNoRun());
    }

    #[Test]
    public function is_parse_error_returns_true_for_parse_error_attribute(): void
    {
        $attributes = new Attributes(attribute: Attribute::ParseError);

        $this->assertTrue($attributes->isParseError());
    }
}
