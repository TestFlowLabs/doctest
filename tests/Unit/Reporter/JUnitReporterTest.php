<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Reporter\JUnitReporter;
use TestFlowLabs\DocTest\Executor\ExecutionResult;
use TestFlowLabs\DocTest\Assertion\AssertionParser;

beforeEach(function (): void {
    $this->reporter = new JUnitReporter();
    $this->parser   = new AssertionParser();

    $this->makeBlock = function (string $code, string $file = 'test.md', int $line = 1): CodeBlock {
        $parsed = $this->parser->parse($code);

        return new CodeBlock(
            file: $file,
            startLine: $line,
            rawCode: $code,
            executableCode: $parsed->executableCode,
            attributes: new Attributes(),
            assertions: [],
        );
    };

    $this->makeResult = (fn (bool $passed, string $file = 'test.md', int $line = 1, ?string $error = null, bool $skipped = false, float $duration = 0.1): ExecutionResult => new ExecutionResult(
        passed: $passed,
        codeBlock: ($this->makeBlock)('echo "test";', $file, $line),
        error: $error,
        duration: $duration,
        skipped: $skipped,
    ));
});
test('generates valid xml structure', function (): void {
    $results = [
        ($this->makeResult)(passed: true),
    ];

    $xml = $this->reporter->generate($results);

    $doc = new \DOMDocument();
    expect($doc->loadXML($xml))->toBeTrue('Generated XML is not valid');
    expect($doc->documentElement->tagName)->toBe('testsuites');
});
test('suites element has correct counts', function (): void {
    $results = [
        ($this->makeResult)(passed: true),
        ($this->makeResult)(passed: false, error: 'fail'),
        ($this->makeResult)(passed: true, skipped: true),
    ];

    $xml = $this->reporter->generate($results);
    $doc = new \DOMDocument();
    $doc->loadXML($xml);

    $testsuites = $doc->documentElement;
    expect($testsuites->getAttribute('tests'))->toBe('3');
    expect($testsuites->getAttribute('failures'))->toBe('1');
});
test('creates testsuite per file', function (): void {
    $results = [
        ($this->makeResult)(passed: true, file: 'docs/api.md'),
        ($this->makeResult)(passed: true, file: 'docs/guide.md'),
    ];

    $xml = $this->reporter->generate($results);
    $doc = new \DOMDocument();
    $doc->loadXML($xml);

    $suites = $doc->getElementsByTagName('testsuite');
    expect($suites->length)->toBe(2);

    $names = [];
    for ($i = 0; $i < $suites->length; $i++) {
        $names[] = $suites->item($i)->getAttribute('name');
    }
    expect($names)->toContain('docs/api.md');
    expect($names)->toContain('docs/guide.md');
});
test('creates testcase per block', function (): void {
    $results = [
        ($this->makeResult)(passed: true, file: 'test.md', line: 5),
        ($this->makeResult)(passed: true, file: 'test.md', line: 15),
    ];

    $xml = $this->reporter->generate($results);
    $doc = new \DOMDocument();
    $doc->loadXML($xml);

    $testcases = $doc->getElementsByTagName('testcase');
    expect($testcases->length)->toBe(2);
});
test('failure element includes error message', function (): void {
    $results = [
        ($this->makeResult)(passed: false, error: 'Expected "hello" but got "world"'),
    ];

    $xml = $this->reporter->generate($results);
    $doc = new \DOMDocument();
    $doc->loadXML($xml);

    $failures = $doc->getElementsByTagName('failure');
    expect($failures->length)->toBe(1);
    $this->assertStringContainsString('Expected "hello" but got "world"', $failures->item(0)->textContent);
});
test('skipped element for ignored blocks', function (): void {
    $results = [
        ($this->makeResult)(passed: true, skipped: true),
    ];

    $xml = $this->reporter->generate($results);
    $doc = new \DOMDocument();
    $doc->loadXML($xml);

    $skipped = $doc->getElementsByTagName('skipped');
    expect($skipped->length)->toBe(1);
});
test('writes to file path', function (): void {
    $results = [
        ($this->makeResult)(passed: true),
    ];

    $filePath = sys_get_temp_dir().'/doctest_junit_'.uniqid().'.xml';

    $this->reporter->generateToFile($results, $filePath);

    expect($filePath)->toBeFile();
    $content = file_get_contents($filePath);
    $this->assertStringContainsString('<?xml', $content);
    $this->assertStringContainsString('testsuites', $content);

    unlink($filePath);
});
test('case includes time attribute', function (): void {
    $results = [
        ($this->makeResult)(passed: true, duration: 0.25),
    ];

    $xml = $this->reporter->generate($results);
    $doc = new \DOMDocument();
    $doc->loadXML($xml);

    $testcase = $doc->getElementsByTagName('testcase')->item(0);
    expect($testcase->getAttribute('time'))->toBe('0.25');
});
test('generates valid xml for empty results', function (): void {
    $xml = $this->reporter->generate([]);
    $doc = new \DOMDocument();

    expect($doc->loadXML($xml))->toBeTrue();
    expect($doc->documentElement->getAttribute('tests'))->toBe('0');
});
test('xml escapes special characters in error', function (): void {
    $results = [
        ($this->makeResult)(passed: false, error: 'Expected "<div>" & got \'none\''),
    ];

    $xml = $this->reporter->generate($results);
    $doc = new \DOMDocument();

    expect($doc->loadXML($xml))->toBeTrue('XML with special chars should be valid');
    $failures = $doc->getElementsByTagName('failure');
    $this->assertStringContainsString('<div>', $failures->item(0)->textContent);
});
test('suite has correct test count', function (): void {
    $results = [
        ($this->makeResult)(passed: true, file: 'a.md', line: 1),
        ($this->makeResult)(passed: false, file: 'a.md', line: 5, error: 'fail'),
        ($this->makeResult)(passed: true, file: 'a.md', line: 10),
    ];

    $xml = $this->reporter->generate($results);
    $doc = new \DOMDocument();
    $doc->loadXML($xml);

    $suite = $doc->getElementsByTagName('testsuite')->item(0);
    expect($suite->getAttribute('tests'))->toBe('3');
    expect($suite->getAttribute('failures'))->toBe('1');
});
