<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\CodeBlock;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Assertion\OutputAssertion;

final class CodeBlockTest extends TestCase
{
    #[Test]
    public function construction_with_all_properties(): void
    {
        $attributes = new Attributes();
        $assertions = [new OutputAssertion('Hello', 5)];

        $block = new CodeBlock(
            file: 'docs/README.md',
            startLine: 10,
            rawCode: 'echo "Hello";'."\n".'// Output: Hello',
            executableCode: 'echo "Hello";',
            attributes: $attributes,
            assertions: $assertions,
        );

        $this->assertSame('docs/README.md', $block->file);
        $this->assertSame(10, $block->startLine);
        $this->assertSame('echo "Hello";'."\n".'// Output: Hello', $block->rawCode);
        $this->assertSame('echo "Hello";', $block->executableCode);
        $this->assertSame($attributes, $block->attributes);
        $this->assertSame($assertions, $block->assertions);
    }

    #[Test]
    public function block_with_no_assertions(): void
    {
        $block = new CodeBlock(
            file: 'README.md',
            startLine: 1,
            rawCode: '$x = 42;',
            executableCode: '$x = 42;',
            attributes: new Attributes(),
            assertions: [],
        );

        $this->assertEmpty($block->assertions);
    }

    #[Test]
    public function block_with_multiple_assertions(): void
    {
        $assertions = [
            new OutputAssertion('first', 2),
            new OutputAssertion('second', 5),
        ];

        $block = new CodeBlock(
            file: 'test.md',
            startLine: 1,
            rawCode: 'code',
            executableCode: 'code',
            attributes: new Attributes(),
            assertions: $assertions,
        );

        $this->assertCount(2, $block->assertions);
    }
}
