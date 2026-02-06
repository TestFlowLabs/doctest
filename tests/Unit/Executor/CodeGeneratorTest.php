<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Executor;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\CodeBlock\Attribute;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\Executor\CodeGenerator;

final class CodeGeneratorTest extends TestCase
{
    private CodeGenerator $generator;

    private AssertionParser $assertionParser;

    protected function setUp(): void
    {
        $this->generator = new CodeGenerator();
        $this->assertionParser = new AssertionParser();
    }

    protected function tearDown(): void
    {
        // Clean up generated files
        $dir = sys_get_temp_dir() . '/doctest';

        if (is_dir($dir)) {
            array_map(unlink(...), glob($dir . '/*.php') ?: []);
        }
    }

    private function makeBlock(string $code, ?Attribute $attribute = null, ?string $throwsClass = null, ?string $throwsMessage = null): CodeBlock
    {
        $parsed = $this->assertionParser->parse($code);

        return new CodeBlock(
            file: 'test.md',
            startLine: 1,
            rawCode: $code,
            executableCode: $parsed->executableCode,
            attributes: new Attributes(
                attribute: $attribute,
                throwsClass: $throwsClass,
                throwsMessage: $throwsMessage,
            ),
            assertions: $parsed->assertions,
        );
    }

    #[Test]
    public function generates_valid_php_file_for_simple_echo(): void
    {
        $block = $this->makeBlock('echo "Hello World";');
        $filePath = $this->generator->generate($block);

        $this->assertFileExists($filePath);
        $this->assertStringStartsWith('<?php', file_get_contents($filePath));
    }

    #[Test]
    public function generates_segment_based_output_capture(): void
    {
        $block = $this->makeBlock("echo \"Hello\";\n// Output: Hello");
        $filePath = $this->generator->generate($block);
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('ob_start()', $content);
        $this->assertStringContainsString('ob_get_clean()', $content);
    }

    #[Test]
    public function generates_multiple_segments_for_multiple_output_assertions(): void
    {
        $block = $this->makeBlock("echo \"a\";\n// Output: a\necho \"b\";\n// Output: b");
        $filePath = $this->generator->generate($block);
        $content = file_get_contents($filePath);

        $this->assertSame(2, substr_count($content, 'ob_start()'));
        $this->assertSame(2, substr_count($content, 'ob_get_clean()'));
    }

    #[Test]
    public function generates_expect_assertion_evaluation(): void
    {
        $block = $this->makeBlock("\$x = 42;\n// Expect: \$x === 42");
        $filePath = $this->generator->generate($block);
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('$x === 42', $content);
    }

    #[Test]
    public function generates_throws_wrapper(): void
    {
        $block = $this->makeBlock(
            'throw new RuntimeException("test");',
            Attribute::Throws,
            'RuntimeException',
            'test',
        );
        $filePath = $this->generator->generate($block);
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('try', $content);
        $this->assertStringContainsString('catch', $content);
    }

    #[Test]
    public function generates_raw_code_for_parse_error(): void
    {
        $block = $this->makeBlock('$x = {invalid;', Attribute::ParseError);
        $filePath = $this->generator->generate($block);
        $content = file_get_contents($filePath);

        // parse_error blocks get raw code without instrumentation
        $this->assertStringContainsString('$x = {invalid;', $content);
        $this->assertStringNotContainsString('ob_start()', $content);
    }

    #[Test]
    public function writes_results_to_stderr_as_json(): void
    {
        $block = $this->makeBlock("echo \"test\";\n// Output: test");
        $filePath = $this->generator->generate($block);
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('STDERR', $content);
        $this->assertStringContainsString('json_encode', $content);
    }

    #[Test]
    public function handles_block_with_no_assertions(): void
    {
        $block = $this->makeBlock('$x = 42;');
        $filePath = $this->generator->generate($block);
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('$x = 42;', $content);
        // No ob_start for blocks without output assertions
        $this->assertStringNotContainsString('ob_start()', $content);
    }

    #[Test]
    public function handles_mixed_output_and_expect(): void
    {
        $block = $this->makeBlock("echo \"Hi\";\n// Output: Hi\n\$x = 1;\n// Expect: \$x === 1");
        $filePath = $this->generator->generate($block);
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('ob_start()', $content);
        $this->assertStringContainsString('$x === 1', $content);
    }

    #[Test]
    public function generated_file_passes_syntax_check(): void
    {
        $block = $this->makeBlock("echo \"Hello\";\n// Output: Hello");
        $filePath = $this->generator->generate($block);

        $output = [];
        $exitCode = 0;
        exec(PHP_BINARY . ' -l ' . escapeshellarg($filePath) . ' 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, 'Generated PHP file has syntax errors: ' . implode("\n", $output));
    }
}
