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
        );
    };
});
afterEach(function (): void {
    $dir = sys_get_temp_dir().'/doctest';

    if (is_dir($dir)) {
        array_map(unlink(...), glob($dir.'/*.php') ?: []);
    }
});
test('generates concatenated file from multiple blocks', function (): void {
    $blocks = [
        ($this->makeBlock)('$counter = 0;'),
        ($this->makeBlock)('$counter++;', assertions: [new ExpectAssertion('$counter === 1', 2)]),
    ];

    $filePath = $this->generator->generateGroup($blocks);

    expect($filePath)->toBeFile();
    $content = file_get_contents($filePath);
    $this->assertStringContainsString('$counter = 0;', $content);
    $this->assertStringContainsString('$counter++', $content);
});
test('preserves block execution order', function (): void {
    $blocks = [
        ($this->makeBlock)('$x = 1;'),
        ($this->makeBlock)('$x = 2;'),
        ($this->makeBlock)('$x = 3;'),
    ];

    $filePath = $this->generator->generateGroup($blocks);
    $content  = file_get_contents($filePath);

    $pos1 = strpos($content, '$x = 1;');
    $pos2 = strpos($content, '$x = 2;');
    $pos3 = strpos($content, '$x = 3;');

    expect($pos1)->toBeLessThan($pos2);
    expect($pos2)->toBeLessThan($pos3);
});
test('each block has independent assertion instrumentation', function (): void {
    $blocks = [
        ($this->makeBlock)('echo "a";', assertions: [new OutputAssertion('a', 1)]),
        ($this->makeBlock)('echo "b";', assertions: [new OutputAssertion('b', 1)]),
    ];

    $filePath = $this->generator->generateGroup($blocks);
    $content  = file_get_contents($filePath);

    expect(substr_count($content, 'ob_start()'))->toBe(2);
    expect(substr_count($content, 'ob_get_clean()'))->toBe(2);
});
test('generates single stderr json', function (): void {
    $blocks = [
        ($this->makeBlock)('echo "a";', assertions: [new OutputAssertion('a', 1)]),
        ($this->makeBlock)('echo "b";', assertions: [new OutputAssertion('b', 1)]),
    ];

    $filePath = $this->generator->generateGroup($blocks);
    $content  = file_get_contents($filePath);

    expect(substr_count($content, 'fwrite(STDERR'))->toBe(1);
});
test('generated group file passes syntax check', function (): void {
    $blocks = [
        ($this->makeBlock)('$x = 1;', assertions: [new ExpectAssertion('$x === 1', 2)]),
        ($this->makeBlock)('echo "hello";', assertions: [new OutputAssertion('hello', 1)]),
    ];

    $filePath = $this->generator->generateGroup($blocks);

    $output   = [];
    $exitCode = 0;
    exec(PHP_BINARY.' -l '.escapeshellarg((string) $filePath).' 2>&1', $output, $exitCode);

    expect($exitCode)->toBe(0, 'Generated group file has syntax errors: '.implode("\n", $output));
});
