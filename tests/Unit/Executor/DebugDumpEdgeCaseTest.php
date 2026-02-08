<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Executor\Executor;
use TestFlowLabs\DocTest\CodeBlock\Attribute;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\Assertion\OutputAssertion;

beforeEach(function (): void {
    $this->executor        = new Executor();
    $this->assertionParser = new AssertionParser();

    $this->makeBlock = function (string $code, ?Attribute $attribute = null, ?string $throwsClass = null, array $assertions = [], ?string $group = null): CodeBlock {
        $parsed = $this->assertionParser->parse($code);

        return new CodeBlock(
            file: 'test.md',
            startLine: 1,
            rawCode: $code,
            executableCode: $parsed->executableCode,
            attributes: new Attributes(
                attribute: $attribute,
                group: $group,
                throwsClass: $throwsClass,
            ),
            assertions: $assertions,
        );
    };
});
test('dd as only marker in block still passes', function (): void {
    $block  = ($this->makeBlock)('$x = 42; // => dd()');
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
    expect($result->debugOutputs)->toHaveCount(1);
    expect($result->assertionDetails)->toBeEmpty();
});
test('dd with void expression captures null', function (): void {
    $block  = ($this->makeBlock)('$arr = []; array_push($arr, 1); // => dd()');
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
    expect($result->debugOutputs)->toHaveCount(1);
    // array_push returns int (count) in PHP 8+
    expect($result->debugOutputs[0]->value)->not->toBeEmpty();
});
test('dd combined with setup and teardown in group', function (): void {
    $blocks = [
        ($this->makeBlock)('$items = []; $items[] = "a";', attribute: Attribute::Setup, group: 'setup-dd'),
        ($this->makeBlock)('count($items); // => dd()', group: 'setup-dd'),
        ($this->makeBlock)('unset($items);', attribute: Attribute::Teardown, group: 'setup-dd'),
    ];

    $results = $this->executor->executeAll($blocks);

    // setup and teardown are not in results, only the middle block
    $normalResults = array_values(array_filter($results, fn ($r) => !$r->skipped));
    expect($normalResults)->toHaveCount(1);
    expect($normalResults[0]->passed)->toBeTrue();
    expect($normalResults[0]->debugOutputs)->toHaveCount(1);
    expect($normalResults[0]->debugOutputs[0]->value)->toBe('1');
});
test('dd in block with html output assertion both work', function (): void {
    $block = ($this->makeBlock)(
        "\$x = 42; // => dd()\necho \"result: \$x\";",
        assertions: [new OutputAssertion('result: 42', 3)],
    );
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
    expect($result->debugOutputs)->toHaveCount(1);
    expect($result->debugOutputs[0]->value)->toBe('42');
});
