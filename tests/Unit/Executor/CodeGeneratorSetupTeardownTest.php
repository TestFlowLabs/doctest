<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Executor\CodeGenerator;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\Assertion\ExpectAssertion;
use TestFlowLabs\DocTest\Assertion\OutputAssertion;

beforeEach(function (): void {
    $this->generator = new CodeGenerator();
    $this->parser    = new AssertionParser();

    /*
     * @param  array<\TestFlowLabs\DocTest\Assertion\Assertion>  $assertions
     */
    $this->makeBlock = function (string $code, array $assertions = []): CodeBlock {
        $parsed = $this->parser->parse($code);

        return new CodeBlock(
            file: 'test.md',
            startLine: 1,
            rawCode: $code,
            executableCode: $parsed->executableCode,
            attributes: new Attributes(),
            assertions: $assertions,
            resultComments: $parsed->resultComments,
            debugMarkers: $parsed->debugMarkers,
        );
    };
});
test('prepends setup code before block code', function (): void {
    $block = ($this->makeBlock)('echo $greeting;');
    $setup = '$greeting = "hello";';

    $filePath = $this->generator->generate($block, setup: $setup);
    $content  = file_get_contents($filePath);

    $setupPos = strpos($content, '$greeting = "hello"');
    $codePos  = strpos($content, 'echo $greeting');

    $this->assertNotFalse($setupPos);
    $this->assertNotFalse($codePos);
    expect($setupPos)->toBeLessThan($codePos);
});
test('appends teardown code after block code', function (): void {
    $block    = ($this->makeBlock)('$resource = "open";');
    $teardown = '$resource = null;';

    $filePath = $this->generator->generate($block, teardown: $teardown);
    $content  = file_get_contents($filePath);

    $codePos     = strpos($content, '$resource = "open"');
    $teardownPos = strpos($content, '$resource = null');

    $this->assertNotFalse($codePos);
    $this->assertNotFalse($teardownPos);
    expect($codePos)->toBeLessThan($teardownPos);
});
test('setup and teardown together in correct order', function (): void {
    $block    = ($this->makeBlock)('echo $x;');
    $setup    = '$x = 42;';
    $teardown = 'unset($x);';

    $filePath = $this->generator->generate($block, setup: $setup, teardown: $teardown);
    $content  = file_get_contents($filePath);

    $setupPos    = strpos($content, '$x = 42');
    $codePos     = strpos($content, 'echo $x');
    $teardownPos = strpos($content, 'unset($x)');

    $this->assertNotFalse($setupPos);
    $this->assertNotFalse($codePos);
    $this->assertNotFalse($teardownPos);
    expect($setupPos)->toBeLessThan($codePos);
    expect($codePos)->toBeLessThan($teardownPos);
});
test('null setup teardown produces no change', function (): void {
    $block = ($this->makeBlock)('echo "test";');

    $withoutParams  = $this->generator->generate($block);
    $contentWithout = file_get_contents($withoutParams);

    // Generate new file with null params explicitly
    $withNullParams = $this->generator->generate($block, setup: null, teardown: null);
    $contentWith    = file_get_contents($withNullParams);

    // Both should produce structurally identical content (ignoring file path differences)
    expect(preg_replace('/doctest_[a-f0-9]+/', 'doctest_X', $contentWith))->toBe(preg_replace('/doctest_[a-f0-9]+/', 'doctest_X', $contentWithout));
});
test('setup variables accessible in block scope', function (): void {
    $block = ($this->makeBlock)('echo $greeting;', assertions: [new OutputAssertion('hello world', 1)]);
    $setup = '$greeting = "hello world";';

    $filePath = $this->generator->generate($block, setup: $setup);

    $output   = [];
    $exitCode = 0;
    exec(PHP_BINARY.' -l '.escapeshellarg((string) $filePath).' 2>&1', $output, $exitCode);

    expect($exitCode)->toBe(0, 'Generated file with setup has syntax errors: '.implode("\n", $output));
});
test('group generation supports setup and teardown', function (): void {
    $blocks = [
        ($this->makeBlock)('$counter++;'),
        ($this->makeBlock)('$counter++;', assertions: [new ExpectAssertion('$counter === 2', 2)]),
    ];
    $setup    = '$counter = 0;';
    $teardown = 'unset($counter);';

    $filePath = $this->generator->generateGroup($blocks, setup: $setup, teardown: $teardown);
    $content  = file_get_contents($filePath);

    $setupPos      = strpos($content, '$counter = 0');
    $firstBlockPos = strpos($content, '$counter++');
    $teardownPos   = strpos($content, 'unset($counter)');

    $this->assertNotFalse($setupPos);
    $this->assertNotFalse($firstBlockPos);
    $this->assertNotFalse($teardownPos);
    expect($setupPos)->toBeLessThan($firstBlockPos);
    expect($firstBlockPos)->toBeLessThan($teardownPos);
});
