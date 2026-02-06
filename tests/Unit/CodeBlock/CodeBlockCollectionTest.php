<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\CodeBlock;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\CodeBlock\Attribute;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\CodeBlockCollection;

final class CodeBlockCollectionTest extends TestCase
{
    private function makeBlock(
        ?Attribute $attribute = null,
        ?string $group = null,
        string $code = '$x = 1;',
    ): CodeBlock {
        return new CodeBlock(
            file: 'test.md',
            startLine: 1,
            rawCode: $code,
            executableCode: $code,
            attributes: new Attributes(attribute: $attribute, group: $group),
            assertions: [],
        );
    }

    #[Test]
    public function construction_from_array(): void
    {
        $blocks = [$this->makeBlock(), $this->makeBlock()];
        $collection = new CodeBlockCollection($blocks);

        $this->assertCount(2, $collection);
    }

    #[Test]
    public function is_iterable(): void
    {
        $block = $this->makeBlock();
        $collection = new CodeBlockCollection([$block]);

        $items = iterator_to_array($collection);
        $this->assertSame($block, $items[0]);
    }

    #[Test]
    public function get_by_group_returns_matching_blocks(): void
    {
        $grouped = $this->makeBlock(group: 'order-flow');
        $ungrouped = $this->makeBlock();

        $collection = new CodeBlockCollection([$grouped, $ungrouped]);
        $result = $collection->getByGroup('order-flow');

        $this->assertCount(1, $result);
        $this->assertSame($grouped, iterator_to_array($result)[0]);
    }

    #[Test]
    public function get_setup_blocks_returns_setup_blocks(): void
    {
        $setup = $this->makeBlock(attribute: Attribute::Setup);
        $regular = $this->makeBlock();

        $collection = new CodeBlockCollection([$setup, $regular]);
        $result = $collection->getSetupBlocks();

        $this->assertCount(1, $result);
    }

    #[Test]
    public function get_teardown_blocks_returns_teardown_blocks(): void
    {
        $teardown = $this->makeBlock(attribute: Attribute::Teardown);
        $regular = $this->makeBlock();

        $collection = new CodeBlockCollection([$teardown, $regular]);
        $result = $collection->getTeardownBlocks();

        $this->assertCount(1, $result);
    }

    #[Test]
    public function get_regular_blocks_excludes_setup_and_teardown(): void
    {
        $setup = $this->makeBlock(attribute: Attribute::Setup);
        $teardown = $this->makeBlock(attribute: Attribute::Teardown);
        $regular = $this->makeBlock();

        $collection = new CodeBlockCollection([$setup, $teardown, $regular]);
        $result = $collection->getRegularBlocks();

        $this->assertCount(1, $result);
    }

    #[Test]
    public function empty_collection(): void
    {
        $collection = new CodeBlockCollection([]);

        $this->assertCount(0, $collection);
        $this->assertEmpty(iterator_to_array($collection));
    }
}
