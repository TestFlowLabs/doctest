<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Reporter;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Reporter\JsonReporter;
use TestFlowLabs\DocTest\Executor\ExecutionResult;
use TestFlowLabs\DocTest\Assertion\AssertionParser;

final class JsonReporterTest extends TestCase
{
    private JsonReporter $reporter;
    private AssertionParser $parser;

    protected function setUp(): void
    {
        $this->reporter = new JsonReporter();
        $this->parser   = new AssertionParser();
    }

    private function makeResult(
        bool $passed,
        string $file = 'test.md',
        int $line = 1,
        ?string $error = null,
        ?string $actualOutput = null,
        ?string $expectedOutput = null,
        ?string $diff = null,
        bool $skipped = false,
        float $duration = 0.1,
    ): ExecutionResult {
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
    }

    #[Test]
    public function generates_valid_json(): void
    {
        $results = [$this->makeResult(passed: true)];

        $json    = $this->reporter->generate($results);
        $decoded = json_decode($json, true);

        $this->assertNotNull($decoded, 'Generated output is not valid JSON');
    }

    #[Test]
    public function contains_file_and_block_results(): void
    {
        $results = [
            $this->makeResult(passed: true, file: 'docs/api.md', line: 5),
            $this->makeResult(passed: true, file: 'docs/api.md', line: 15),
        ];

        $json = $this->reporter->generate($results);
        $data = json_decode($json, true);

        $this->assertArrayHasKey('files', $data);
        $this->assertCount(1, $data['files']);
        $this->assertSame('docs/api.md', $data['files'][0]['file']);
        $this->assertCount(2, $data['files'][0]['blocks']);
    }

    #[Test]
    public function failure_details_include_expected_actual_diff(): void
    {
        $results = [
            $this->makeResult(
                passed: false,
                error: 'Output mismatch',
                actualOutput: 'world',
                expectedOutput: 'hello',
                diff: '- hello\n+ world',
            ),
        ];

        $json = $this->reporter->generate($results);
        $data = json_decode($json, true);

        $block = $data['files'][0]['blocks'][0];
        $this->assertFalse($block['passed']);
        $this->assertSame('Output mismatch', $block['error']);
        $this->assertSame('hello', $block['expected']);
        $this->assertSame('world', $block['actual']);
        $this->assertSame('- hello\n+ world', $block['diff']);
    }

    #[Test]
    public function skipped_blocks_marked(): void
    {
        $results = [$this->makeResult(passed: true, skipped: true)];

        $json = $this->reporter->generate($results);
        $data = json_decode($json, true);

        $this->assertTrue($data['files'][0]['blocks'][0]['skipped']);
    }

    #[Test]
    public function summary_statistics_included(): void
    {
        $results = [
            $this->makeResult(passed: true),
            $this->makeResult(passed: false, error: 'fail'),
            $this->makeResult(passed: true, skipped: true),
        ];

        $json = $this->reporter->generate($results);
        $data = json_decode($json, true);

        $this->assertArrayHasKey('summary', $data);
        $this->assertSame(3, $data['summary']['total']);
        $this->assertSame(1, $data['summary']['passed']);
        $this->assertSame(1, $data['summary']['failed']);
        $this->assertSame(1, $data['summary']['skipped']);
    }

    #[Test]
    public function writes_to_file_path(): void
    {
        $results  = [$this->makeResult(passed: true)];
        $filePath = sys_get_temp_dir().'/doctest_json_'.uniqid().'.json';

        $this->reporter->generateToFile($results, $filePath);

        $this->assertFileExists($filePath);
        $content = file_get_contents($filePath);
        $decoded = json_decode($content, true);
        $this->assertNotNull($decoded);
        $this->assertArrayHasKey('summary', $decoded);

        unlink($filePath);
    }
}
