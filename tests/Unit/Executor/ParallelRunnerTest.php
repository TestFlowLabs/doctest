<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Executor;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Executor\ParallelRunner;

final class ParallelRunnerTest extends TestCase
{
    #[Test]
    public function distributes_files_across_workers(): void
    {
        $runner = new ParallelRunner(workers: 2);
        $batches = $runner->distribute(['a.md', 'b.md', 'c.md', 'd.md']);

        $this->assertCount(2, $batches);
        $this->assertSame(['a.md', 'c.md'], $batches[0]);
        $this->assertSame(['b.md', 'd.md'], $batches[1]);
    }

    #[Test]
    public function single_worker_gets_all_files(): void
    {
        $runner = new ParallelRunner(workers: 1);
        $batches = $runner->distribute(['a.md', 'b.md', 'c.md']);

        $this->assertCount(1, $batches);
        $this->assertSame(['a.md', 'b.md', 'c.md'], $batches[0]);
    }

    #[Test]
    public function more_workers_than_files(): void
    {
        $runner = new ParallelRunner(workers: 5);
        $batches = $runner->distribute(['a.md', 'b.md']);

        // Only 2 non-empty batches
        $nonEmpty = array_filter($batches, fn (array $b) => $b !== []);
        $this->assertCount(2, $nonEmpty);
    }

    #[Test]
    public function empty_file_list_returns_empty_batches(): void
    {
        $runner = new ParallelRunner(workers: 3);
        $batches = $runner->distribute([]);

        $nonEmpty = array_filter($batches, fn (array $b) => $b !== []);
        $this->assertEmpty($nonEmpty);
    }

    #[Test]
    public function workers_count_is_accessible(): void
    {
        $runner = new ParallelRunner(workers: 4);

        $this->assertSame(4, $runner->workers);
    }
}
