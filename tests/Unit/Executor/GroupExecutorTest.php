<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Executor\Executor;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\Assertion\ExpectAssertion;
use TestFlowLabs\DocTest\Assertion\OutputAssertion;

beforeEach(function (): void {
    $this->executor = new Executor();
    $this->parser   = new AssertionParser();

    /*
     * @param  array<\TestFlowLabs\DocTest\Assertion\Assertion>  $assertions
     */
    $this->makeBlock = function (string $code, ?string $group = null, array $assertions = []): CodeBlock {
        $parsed = $this->parser->parse($code);

        return new CodeBlock(
            file: 'test.md',
            startLine: 1,
            rawCode: $code,
            executableCode: $parsed->executableCode,
            attributes: new Attributes(group: $group),
            assertions: $assertions,
        );
    };
});
test('executes grouped blocks sharing scope', function (): void {
    $blocks = [
        ($this->makeBlock)('$counter = 0;', group: 'mygroup'),
        ($this->makeBlock)('$counter++;', group: 'mygroup', assertions: [new ExpectAssertion('$counter === 1', 2)]),
    ];

    $results = $this->executor->executeGroup($blocks);

    expect($results)->toHaveCount(2);
    foreach ($results as $result) {
        expect($result->passed)->toBeTrue('Group block failed: '.($result->error ?? ''));
    }
});
test('variables from earlier blocks available in later blocks', function (): void {
    $blocks = [
        ($this->makeBlock)('$name = "doctest";', group: 'scope'),
        ($this->makeBlock)('echo $name;', group: 'scope', assertions: [new OutputAssertion('doctest', 1)]),
    ];

    $results = $this->executor->executeGroup($blocks);

    expect($results)->toHaveCount(2);
    expect($results[1]->passed)->toBeTrue('Later block cannot see earlier variable: '.($results[1]->error ?? ''));
});
test('each blocks assertions evaluated independently', function (): void {
    $blocks = [
        ($this->makeBlock)('echo "a";', group: 'ind', assertions: [new OutputAssertion('a', 1)]),
        ($this->makeBlock)('echo "b";', group: 'ind', assertions: [new OutputAssertion('b', 1)]),
    ];

    $results = $this->executor->executeGroup($blocks);

    expect($results)->toHaveCount(2);
    expect($results[0]->passed)->toBeTrue();
    expect($results[1]->passed)->toBeTrue();
});
test('returns failure for failing assertion in group', function (): void {
    $blocks = [
        ($this->makeBlock)('$x = 1;', group: 'fail'),
        ($this->makeBlock)('echo "wrong";', group: 'fail', assertions: [new OutputAssertion('right', 1)]),
    ];

    $results = $this->executor->executeGroup($blocks);

    expect($results)->toHaveCount(2);
    expect($results[0]->passed)->toBeTrue();
    expect($results[1]->passed)->toBeFalse();
});
test('group execution returns results mapped to blocks', function (): void {
    $block1 = ($this->makeBlock)('$a = 1;', group: 'map');
    $block2 = ($this->makeBlock)('$b = 2;', group: 'map', assertions: [new ExpectAssertion('$a + $b === 3', 2)]);

    $results = $this->executor->executeGroup([$block1, $block2]);

    expect($results[0]->codeBlock)->toBe($block1);
    expect($results[1]->codeBlock)->toBe($block2);
});
test('output assertions work within group blocks', function (): void {
    $blocks = [
        ($this->makeBlock)('$prefix = "Hello";', group: 'out'),
        ($this->makeBlock)('echo $prefix . " World";', group: 'out', assertions: [new OutputAssertion('Hello World', 1)]),
    ];

    $results = $this->executor->executeGroup($blocks);

    expect($results[1]->passed)->toBeTrue('Output assertion in group failed: '.($results[1]->error ?? ''));
    expect($results[1]->actualOutput)->toBe('Hello World');
});
test('group with single block works', function (): void {
    $blocks = [
        ($this->makeBlock)('echo "solo";', group: 'single', assertions: [new OutputAssertion('solo', 1)]),
    ];

    $results = $this->executor->executeGroup($blocks);

    expect($results)->toHaveCount(1);
    expect($results[0]->passed)->toBeTrue();
});
