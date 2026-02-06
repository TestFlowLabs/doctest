<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Executor;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\Executor\Executor;

final class GroupExecutorTest extends TestCase
{
    private Executor $executor;

    private AssertionParser $parser;

    protected function setUp(): void
    {
        $this->executor = new Executor();
        $this->parser = new AssertionParser();
    }

    private function makeBlock(string $code, ?string $group = null): CodeBlock
    {
        $parsed = $this->parser->parse($code);

        return new CodeBlock(
            file: 'test.md',
            startLine: 1,
            rawCode: $code,
            executableCode: $parsed->executableCode,
            attributes: new Attributes(group: $group),
            assertions: $parsed->assertions,
        );
    }

    #[Test]
    public function executes_grouped_blocks_sharing_scope(): void
    {
        $blocks = [
            $this->makeBlock('$counter = 0;', group: 'mygroup'),
            $this->makeBlock("\$counter++;\n// Expect: \$counter === 1", group: 'mygroup'),
        ];

        $results = $this->executor->executeGroup($blocks);

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertTrue($result->passed, 'Group block failed: ' . ($result->error ?? ''));
        }
    }

    #[Test]
    public function variables_from_earlier_blocks_available_in_later_blocks(): void
    {
        $blocks = [
            $this->makeBlock('$name = "doctest";', group: 'scope'),
            $this->makeBlock("echo \$name;\n// Output: doctest", group: 'scope'),
        ];

        $results = $this->executor->executeGroup($blocks);

        $this->assertCount(2, $results);
        $this->assertTrue($results[1]->passed, 'Later block cannot see earlier variable: ' . ($results[1]->error ?? ''));
    }

    #[Test]
    public function each_blocks_assertions_evaluated_independently(): void
    {
        $blocks = [
            $this->makeBlock("echo \"a\";\n// Output: a", group: 'ind'),
            $this->makeBlock("echo \"b\";\n// Output: b", group: 'ind'),
        ];

        $results = $this->executor->executeGroup($blocks);

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]->passed);
        $this->assertTrue($results[1]->passed);
    }

    #[Test]
    public function returns_failure_for_failing_assertion_in_group(): void
    {
        $blocks = [
            $this->makeBlock('$x = 1;', group: 'fail'),
            $this->makeBlock("echo \"wrong\";\n// Output: right", group: 'fail'),
        ];

        $results = $this->executor->executeGroup($blocks);

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]->passed);
        $this->assertFalse($results[1]->passed);
    }

    #[Test]
    public function group_execution_returns_results_mapped_to_blocks(): void
    {
        $block1 = $this->makeBlock('$a = 1;', group: 'map');
        $block2 = $this->makeBlock("\$b = 2;\n// Expect: \$a + \$b === 3", group: 'map');

        $results = $this->executor->executeGroup([$block1, $block2]);

        $this->assertSame($block1, $results[0]->codeBlock);
        $this->assertSame($block2, $results[1]->codeBlock);
    }

    #[Test]
    public function output_assertions_work_within_group_blocks(): void
    {
        $blocks = [
            $this->makeBlock('$prefix = "Hello";', group: 'out'),
            $this->makeBlock("echo \$prefix . \" World\";\n// Output: Hello World", group: 'out'),
        ];

        $results = $this->executor->executeGroup($blocks);

        $this->assertTrue($results[1]->passed, 'Output assertion in group failed: ' . ($results[1]->error ?? ''));
        $this->assertSame('Hello World', $results[1]->actualOutput);
    }

    #[Test]
    public function group_with_single_block_works(): void
    {
        $blocks = [
            $this->makeBlock("echo \"solo\";\n// Output: solo", group: 'single'),
        ];

        $results = $this->executor->executeGroup($blocks);

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]->passed);
    }
}
