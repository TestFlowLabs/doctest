<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Reporter;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\Executor\ExecutionResult;
use TestFlowLabs\DocTest\Reporter\JUnitReporter;

final class JUnitReporterTest extends TestCase
{
    private JUnitReporter $reporter;

    private AssertionParser $parser;

    protected function setUp(): void
    {
        $this->reporter = new JUnitReporter();
        $this->parser = new AssertionParser();
    }

    private function makeBlock(string $code, string $file = 'test.md', int $line = 1): CodeBlock
    {
        $parsed = $this->parser->parse($code);

        return new CodeBlock(
            file: $file,
            startLine: $line,
            rawCode: $code,
            executableCode: $parsed->executableCode,
            attributes: new Attributes(),
            assertions: $parsed->assertions,
        );
    }

    private function makeResult(bool $passed, string $file = 'test.md', int $line = 1, ?string $error = null, bool $skipped = false, float $duration = 0.1): ExecutionResult
    {
        return new ExecutionResult(
            passed: $passed,
            codeBlock: $this->makeBlock('echo "test";', $file, $line),
            error: $error,
            duration: $duration,
            skipped: $skipped,
        );
    }

    #[Test]
    public function generates_valid_xml_structure(): void
    {
        $results = [
            $this->makeResult(passed: true),
        ];

        $xml = $this->reporter->generate($results);

        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($xml), 'Generated XML is not valid');
        $this->assertSame('testsuites', $doc->documentElement->tagName);
    }

    #[Test]
    public function testsuites_element_has_correct_counts(): void
    {
        $results = [
            $this->makeResult(passed: true),
            $this->makeResult(passed: false, error: 'fail'),
            $this->makeResult(passed: true, skipped: true),
        ];

        $xml = $this->reporter->generate($results);
        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        $testsuites = $doc->documentElement;
        $this->assertSame('3', $testsuites->getAttribute('tests'));
        $this->assertSame('1', $testsuites->getAttribute('failures'));
    }

    #[Test]
    public function creates_testsuite_per_file(): void
    {
        $results = [
            $this->makeResult(passed: true, file: 'docs/api.md'),
            $this->makeResult(passed: true, file: 'docs/guide.md'),
        ];

        $xml = $this->reporter->generate($results);
        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        $suites = $doc->getElementsByTagName('testsuite');
        $this->assertSame(2, $suites->length);

        $names = [];
        for ($i = 0; $i < $suites->length; $i++) {
            $names[] = $suites->item($i)->getAttribute('name');
        }
        $this->assertContains('docs/api.md', $names);
        $this->assertContains('docs/guide.md', $names);
    }

    #[Test]
    public function creates_testcase_per_block(): void
    {
        $results = [
            $this->makeResult(passed: true, file: 'test.md', line: 5),
            $this->makeResult(passed: true, file: 'test.md', line: 15),
        ];

        $xml = $this->reporter->generate($results);
        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        $testcases = $doc->getElementsByTagName('testcase');
        $this->assertSame(2, $testcases->length);
    }

    #[Test]
    public function failure_element_includes_error_message(): void
    {
        $results = [
            $this->makeResult(passed: false, error: 'Expected "hello" but got "world"'),
        ];

        $xml = $this->reporter->generate($results);
        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        $failures = $doc->getElementsByTagName('failure');
        $this->assertSame(1, $failures->length);
        $this->assertStringContainsString('Expected "hello" but got "world"', $failures->item(0)->textContent);
    }

    #[Test]
    public function skipped_element_for_ignored_blocks(): void
    {
        $results = [
            $this->makeResult(passed: true, skipped: true),
        ];

        $xml = $this->reporter->generate($results);
        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        $skipped = $doc->getElementsByTagName('skipped');
        $this->assertSame(1, $skipped->length);
    }

    #[Test]
    public function writes_to_file_path(): void
    {
        $results = [
            $this->makeResult(passed: true),
        ];

        $filePath = sys_get_temp_dir() . '/doctest_junit_' . uniqid() . '.xml';

        $this->reporter->generateToFile($results, $filePath);

        $this->assertFileExists($filePath);
        $content = file_get_contents($filePath);
        $this->assertStringContainsString('<?xml', $content);
        $this->assertStringContainsString('testsuites', $content);

        unlink($filePath);
    }

    #[Test]
    public function testcase_includes_time_attribute(): void
    {
        $results = [
            $this->makeResult(passed: true, duration: 0.25),
        ];

        $xml = $this->reporter->generate($results);
        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        $testcase = $doc->getElementsByTagName('testcase')->item(0);
        $this->assertSame('0.25', $testcase->getAttribute('time'));
    }
}
