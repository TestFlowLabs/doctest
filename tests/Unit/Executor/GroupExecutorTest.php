<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Executor;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\Assertion\ExpectAssertion;
use TestFlowLabs\DocTest\Assertion\OutputAssertion;
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

    /**
     * @param array<\TestFlowLabs\DocTest\Assertion\Assertion> $assertions
     */
    private function makeBlock(string $code, ?string $group = null, array $assertions = []): CodeBlock
    {
        $parsed = $this->parser->parse($code);

        return new CodeBlock(
            file: 'test.md',
            startLine: 1,
            rawCode: $code,
            executableCode: $parsed->executableCode,
            attributes: new Attributes(group: $group),
            assertions: $assertions,
        );
    }

    #[Test]
    public function executes_grouped_blocks_sharing_scope(): void
    {
        $blocks = [
            $this->makeBlock('$counter = 0;', group: 'mygroup'),
            $this->makeBlock(
                "\$counter++;",
                group: 'mygroup',
                assertions: [new ExpectAssertion('$counter === 1', 2)],
            ),
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
            $this->makeBlock(
                'echo $name;',
                group: 'scope',
                assertions: [new OutputAssertion('doctest', 1)],
            ),
        ];

        $results = $this->executor->executeGroup($blocks);

        $this->assertCount(2, $results);
        $this->assertTrue($results[1]->passed, 'Later block cannot see earlier variable: ' . ($results[1]->error ?? ''));
    }

    #[Test]
    public function each_blocks_assertions_evaluated_independently(): void
    {
        $blocks = [
            $this->makeBlock(
                'echo "a";',
                group: 'ind',
                assertions: [new OutputAssertion('a', 1)],
            ),
            $this->makeBlock(
                'echo "b";',
                group: 'ind',
                assertions: [new OutputAssertion('b', 1)],
            ),
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
            $this->makeBlock(
                'echo "wrong";',
                group: 'fail',
                assertions: [new OutputAssertion('right', 1)],
            ),
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
        $block2 = $this->makeBlock(
            "\$b = 2;",
            group: 'map',
            assertions: [new ExpectAssertion('$a + $b === 3', 2)],
        );

        $results = $this->executor->executeGroup([$block1, $block2]);

        $this->assertSame($block1, $results[0]->codeBlock);
        $this->assertSame($block2, $results[1]->codeBlock);
    }

    #[Test]
    public function output_assertions_work_within_group_blocks(): void
    {
        $blocks = [
            $this->makeBlock('$prefix = "Hello";', group: 'out'),
            $this->makeBlock(
                'echo $prefix . " World";',
                group: 'out',
                assertions: [new OutputAssertion('Hello World', 1)],
            ),
        ];

        $results = $this->executor->executeGroup($blocks);

        $this->assertTrue($results[1]->passed, 'Output assertion in group failed: ' . ($results[1]->error ?? ''));
        $this->assertSame('Hello World', $results[1]->actualOutput);
    }

    #[Test]
    public function group_with_single_block_works(): void
    {
        $blocks = [
            $this->makeBlock(
                'echo "solo";',
                group: 'single',
                assertions: [new OutputAssertion('solo', 1)],
            ),
        ];

        $results = $this->executor->executeGroup($blocks);

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]->passed);
    }
}
