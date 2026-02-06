<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Executor\Executor;
use TestFlowLabs\DocTest\CodeBlock\Attribute;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\Assertion\OutputAssertion;

beforeEach(function (): void {
    $this->executor = new Executor();
    $this->parser   = new AssertionParser();

    /*
     * @param  array<\TestFlowLabs\DocTest\Assertion\Assertion>  $assertions
     */
    $this->makeBlock = function (string $code, ?Attribute $attribute = null, ?string $group = null, array $assertions = []): CodeBlock {
        $parsed = $this->parser->parse($code);

        return new CodeBlock(
            file: 'test.md',
            startLine: 1,
            rawCode: $code,
            executableCode: $parsed->executableCode,
            attributes: new Attributes(attribute: $attribute, group: $group),
            assertions: $assertions,
        );
    };
});
test('setup code is prepended to block execution', function (): void {
    $blocks = [
        ($this->makeBlock)('$greeting = "hello";', attribute: Attribute::Setup),
        ($this->makeBlock)('echo $greeting;', assertions: [new OutputAssertion('hello', 1)]),
    ];

    $results = $this->executor->executeAll($blocks);

    // Setup block is not executed standalone — only 1 result for the normal block
    expect($results)->toHaveCount(1);
    expect($results[0]->passed)->toBeTrue('Block with setup failed: '.($results[0]->error ?? ''));
});
test('teardown code is appended to block execution', function (): void {
    $blocks = [
        ($this->makeBlock)('echo "done";', assertions: [new OutputAssertion('done', 1)]),
        ($this->makeBlock)('$cleanup = true;', attribute: Attribute::Teardown),
    ];

    $results = $this->executor->executeAll($blocks);

    // Teardown block is not executed standalone — only 1 result
    expect($results)->toHaveCount(1);
    expect($results[0]->passed)->toBeTrue();
});
test('multiple setup blocks concatenated in order', function (): void {
    $blocks = [
        ($this->makeBlock)('$a = 1;', attribute: Attribute::Setup),
        ($this->makeBlock)('$b = 2;', attribute: Attribute::Setup),
        ($this->makeBlock)('echo $a + $b;', assertions: [new OutputAssertion('3', 1)]),
    ];

    $results = $this->executor->executeAll($blocks);

    expect($results)->toHaveCount(1);
    expect($results[0]->passed)->toBeTrue('Multiple setup blocks failed: '.($results[0]->error ?? ''));
});
test('multiple teardown blocks concatenated in order', function (): void {
    $blocks = [
        ($this->makeBlock)('$x = 1;'),
        ($this->makeBlock)('unset($x);', attribute: Attribute::Teardown),
        ($this->makeBlock)('$y = 0;', attribute: Attribute::Teardown),
    ];

    $results = $this->executor->executeAll($blocks);

    expect($results)->toHaveCount(1);
    expect($results[0]->passed)->toBeTrue();
});
test('setup teardown blocks never executed standalone', function (): void {
    $blocks = [
        ($this->makeBlock)('$x = 1;', attribute: Attribute::Setup),
        ($this->makeBlock)('unset($x);', attribute: Attribute::Teardown),
        ($this->makeBlock)('echo "test";', assertions: [new OutputAssertion('test', 1)]),
    ];

    $results = $this->executor->executeAll($blocks);

    // Only the normal block produces a result
    expect($results)->toHaveCount(1);

    // Setup and teardown blocks should not appear in results
    foreach ($results as $result) {
        expect($result->codeBlock->attributes->isSetup())->toBeFalse();
        expect($result->codeBlock->attributes->isTeardown())->toBeFalse();
    }
});
test('grouped blocks receive setup and teardown', function (): void {
    $blocks = [
        ($this->makeBlock)('$base = 10;', attribute: Attribute::Setup),
        ($this->makeBlock)('$base++;', group: 'calc'),
        ($this->makeBlock)('echo $base;', group: 'calc', assertions: [new OutputAssertion('11', 1)]),
        ($this->makeBlock)('unset($base);', attribute: Attribute::Teardown),
    ];

    $results = $this->executor->executeAll($blocks);

    expect($results)->toHaveCount(2);
    foreach ($results as $result) {
        expect($result->passed)->toBeTrue('Grouped block with setup/teardown failed: '.($result->error ?? ''));
    }
});
test('setup variables available in block code', function (): void {
    $blocks = [
        ($this->makeBlock)('$config = ["key" => "value"];', attribute: Attribute::Setup),
        ($this->makeBlock)('echo $config["key"];', assertions: [new OutputAssertion('value', 1)]),
    ];

    $results = $this->executor->executeAll($blocks);

    expect($results)->toHaveCount(1);
    expect($results[0]->passed)->toBeTrue('Setup variables not available: '.($results[0]->error ?? ''));
    expect($results[0]->actualOutput)->toBe('value');
});
