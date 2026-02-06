<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Reporter\JsonReporter;
use TestFlowLabs\DocTest\Executor\ExecutionResult;
use TestFlowLabs\DocTest\Assertion\AssertionParser;

beforeEach(function (): void {
    $this->reporter = new JsonReporter();
    $this->parser   = new AssertionParser();

    $this->makeResult = function (bool $passed, string $file = 'test.md', int $line = 1, ?string $error = null, ?string $actualOutput = null, ?string $expectedOutput = null, ?string $diff = null, bool $skipped = false, float $duration = 0.1): ExecutionResult {
        $parsed = $this->parser->parse('echo "test";');

        return new ExecutionResult(
            passed: $passed,
            codeBlock: new CodeBlock(
                file: $file,
                startLine: $line,
                rawCode: 'echo "test";',
                executableCode: $parsed->executableCode,
                attributes: new Attributes(),
                assertions: [],
            ),
            actualOutput: $actualOutput,
            expectedOutput: $expectedOutput,
            diff: $diff,
            error: $error,
            duration: $duration,
            skipped: $skipped,
        );
    };
});
test('generates valid json', function (): void {
    $results = [($this->makeResult)(passed: true)];

    $json    = $this->reporter->generate($results);
    $decoded = json_decode((string) $json, true);

    expect($decoded)->not->toBeNull('Generated output is not valid JSON');
});
test('contains file and block results', function (): void {
    $results = [
        ($this->makeResult)(passed: true, file: 'docs/api.md', line: 5),
        ($this->makeResult)(passed: true, file: 'docs/api.md', line: 15),
    ];

    $json = $this->reporter->generate($results);
    $data = json_decode((string) $json, true);

    expect($data)->toHaveKey('files');
    expect($data['files'])->toHaveCount(1);
    expect($data['files'][0]['file'])->toBe('docs/api.md');
    expect($data['files'][0]['blocks'])->toHaveCount(2);
});
test('failure details include expected actual diff', function (): void {
    $results = [
        ($this->makeResult)(passed: false, error: 'Output mismatch', actualOutput: 'world', expectedOutput: 'hello', diff: '- hello\n+ world'),
    ];

    $json = $this->reporter->generate($results);
    $data = json_decode((string) $json, true);

    $block = $data['files'][0]['blocks'][0];
    expect($block['passed'])->toBeFalse();
    expect($block['error'])->toBe('Output mismatch');
    expect($block['expected'])->toBe('hello');
    expect($block['actual'])->toBe('world');
    expect($block['diff'])->toBe('- hello\n+ world');
});
test('skipped blocks marked', function (): void {
    $results = [($this->makeResult)(passed: true, skipped: true)];

    $json = $this->reporter->generate($results);
    $data = json_decode((string) $json, true);

    expect($data['files'][0]['blocks'][0]['skipped'])->toBeTrue();
});
test('summary statistics included', function (): void {
    $results = [
        ($this->makeResult)(passed: true),
        ($this->makeResult)(passed: false, error: 'fail'),
        ($this->makeResult)(passed: true, skipped: true),
    ];

    $json = $this->reporter->generate($results);
    $data = json_decode((string) $json, true);

    expect($data)->toHaveKey('summary');
    expect($data['summary']['total'])->toBe(3);
    expect($data['summary']['passed'])->toBe(1);
    expect($data['summary']['failed'])->toBe(1);
    expect($data['summary']['skipped'])->toBe(1);
});
test('writes to file path', function (): void {
    $results  = [($this->makeResult)(passed: true)];
    $filePath = sys_get_temp_dir().'/doctest_json_'.uniqid().'.json';

    $this->reporter->generateToFile($results, $filePath);

    expect($filePath)->toBeFile();
    $content = file_get_contents($filePath);
    $decoded = json_decode($content, true);
    expect($decoded)->not->toBeNull();
    expect($decoded)->toHaveKey('summary');

    unlink($filePath);
});
test('generates valid json for empty results', function (): void {
    $json = $this->reporter->generate([]);
    $data = json_decode((string) $json, true);

    expect($data)->not->toBeNull();
    expect($data['files'])->toBe([]);
    expect($data['summary']['total'])->toBe(0);
});
test('groups multiple files correctly', function (): void {
    $results = [
        ($this->makeResult)(passed: true, file: 'a.md', line: 1),
        ($this->makeResult)(passed: true, file: 'b.md', line: 1),
        ($this->makeResult)(passed: false, file: 'a.md', line: 5, error: 'fail'),
    ];

    $json = $this->reporter->generate($results);
    $data = json_decode((string) $json, true);

    expect($data['files'])->toHaveCount(2);
});
test('block includes duration', function (): void {
    $results = [($this->makeResult)(passed: true, duration: 1.23)];

    $json  = $this->reporter->generate($results);
    $data  = json_decode((string) $json, true);
    $block = $data['files'][0]['blocks'][0];

    expect($block)->toHaveKey('duration');
    expect($block['duration'])->toBe(1.23);
});
