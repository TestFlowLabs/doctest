<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Executor;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\Executor\Executor;
use TestFlowLabs\DocTest\CodeBlock\Attribute;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\Assertion\OutputAssertion;

final class SetupTeardownTest extends TestCase
{
    private Executor $executor;
    private AssertionParser $parser;

    protected function setUp(): void
    {
        $this->executor = new Executor();
        $this->parser   = new AssertionParser();
    }

    /**
     * @param  array<\TestFlowLabs\DocTest\Assertion\Assertion>  $assertions
     */
    private function makeBlock(string $code, ?Attribute $attribute = null, ?string $group = null, array $assertions = []): CodeBlock
    {
        $parsed = $this->parser->parse($code);

        return new CodeBlock(
            file: 'test.md',
            startLine: 1,
            rawCode: $code,
            executableCode: $parsed->executableCode,
            attributes: new Attributes(attribute: $attribute, group: $group),
            assertions: $assertions,
        );
    }

    #[Test]
    public function setup_code_is_prepended_to_block_execution(): void
    {
        $blocks = [
            $this->makeBlock('$greeting = "hello";', attribute: Attribute::Setup),
            $this->makeBlock(
                'echo $greeting;',
                assertions: [new OutputAssertion('hello', 1)],
            ),
        ];

        $results = $this->executor->executeAll($blocks);

        // Setup block is not executed standalone — only 1 result for the normal block
        $this->assertCount(1, $results);
        $this->assertTrue($results[0]->passed, 'Block with setup failed: '.($results[0]->error ?? ''));
    }

    #[Test]
    public function teardown_code_is_appended_to_block_execution(): void
    {
        $blocks = [
            $this->makeBlock(
                'echo "done";',
                assertions: [new OutputAssertion('done', 1)],
            ),
            $this->makeBlock('$cleanup = true;', attribute: Attribute::Teardown),
        ];

        $results = $this->executor->executeAll($blocks);

        // Teardown block is not executed standalone — only 1 result
        $this->assertCount(1, $results);
        $this->assertTrue($results[0]->passed);
    }

    #[Test]
    public function multiple_setup_blocks_concatenated_in_order(): void
    {
        $blocks = [
            $this->makeBlock('$a = 1;', attribute: Attribute::Setup),
            $this->makeBlock('$b = 2;', attribute: Attribute::Setup),
            $this->makeBlock(
                'echo $a + $b;',
                assertions: [new OutputAssertion('3', 1)],
            ),
        ];

        $results = $this->executor->executeAll($blocks);

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]->passed, 'Multiple setup blocks failed: '.($results[0]->error ?? ''));
    }

    #[Test]
    public function multiple_teardown_blocks_concatenated_in_order(): void
    {
        $blocks = [
            $this->makeBlock('$x = 1;'),
            $this->makeBlock('unset($x);', attribute: Attribute::Teardown),
            $this->makeBlock('$y = 0;', attribute: Attribute::Teardown),
        ];

        $results = $this->executor->executeAll($blocks);

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]->passed);
    }

    #[Test]
    public function setup_teardown_blocks_never_executed_standalone(): void
    {
        $blocks = [
            $this->makeBlock('$x = 1;', attribute: Attribute::Setup),
            $this->makeBlock('unset($x);', attribute: Attribute::Teardown),
            $this->makeBlock(
                'echo "test";',
                assertions: [new OutputAssertion('test', 1)],
            ),
        ];

        $results = $this->executor->executeAll($blocks);

        // Only the normal block produces a result
        $this->assertCount(1, $results);
        // Setup and teardown blocks should not appear in results
        foreach ($results as $result) {
            $this->assertFalse($result->codeBlock->attributes->isSetup());
            $this->assertFalse($result->codeBlock->attributes->isTeardown());
        }
    }

    #[Test]
    public function grouped_blocks_receive_setup_and_teardown(): void
    {
        $blocks = [
            $this->makeBlock('$base = 10;', attribute: Attribute::Setup),
            $this->makeBlock('$base++;', group: 'calc'),
            $this->makeBlock(
                'echo $base;',
                group: 'calc',
                assertions: [new OutputAssertion('11', 1)],
            ),
            $this->makeBlock('unset($base);', attribute: Attribute::Teardown),
        ];

        $results = $this->executor->executeAll($blocks);

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertTrue($result->passed, 'Grouped block with setup/teardown failed: '.($result->error ?? ''));
        }
    }

    #[Test]
    public function setup_variables_available_in_block_code(): void
    {
        $blocks = [
            $this->makeBlock('$config = ["key" => "value"];', attribute: Attribute::Setup),
            $this->makeBlock(
                'echo $config["key"];',
                assertions: [new OutputAssertion('value', 1)],
            ),
        ];

        $results = $this->executor->executeAll($blocks);

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]->passed, 'Setup variables not available: '.($results[0]->error ?? ''));
        $this->assertSame('value', $results[0]->actualOutput);
    }
}
