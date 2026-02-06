<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\CodeBlock;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, CodeBlock>
 */
final readonly class CodeBlockCollection implements Countable, IteratorAggregate
{
    /**
     * @param array<CodeBlock> $blocks
     */
    public function __construct(
        private array $blocks,
    ) {}

    public function count(): int
    {
        return count($this->blocks);
    }

    /**
     * @return Traversable<int, CodeBlock>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->blocks);
    }

    public function getByGroup(string $group): self
    {
        return new self(array_values(array_filter(
            $this->blocks,
            static fn(CodeBlock $block): bool => $block->attributes->group === $group,
        )));
    }

    public function getSetupBlocks(): self
    {
        return new self(array_values(array_filter(
            $this->blocks,
            static fn(CodeBlock $block): bool => $block->attributes->isSetup(),
        )));
    }

    public function getTeardownBlocks(): self
    {
        return new self(array_values(array_filter(
            $this->blocks,
            static fn(CodeBlock $block): bool => $block->attributes->isTeardown(),
        )));
    }

    public function getRegularBlocks(): self
    {
        return new self(array_values(array_filter(
            $this->blocks,
            static fn(CodeBlock $block): bool => ! $block->attributes->isSetup()
                && ! $block->attributes->isTeardown(),
        )));
    }
}
