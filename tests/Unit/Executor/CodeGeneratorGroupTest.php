<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Executor;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\Executor\CodeGenerator;

final class CodeGeneratorGroupTest extends TestCase
{
    private CodeGenerator $generator;

    private AssertionParser $parser;

    protected function setUp(): void
    {
        $this->generator = new CodeGenerator();
        $this->parser = new AssertionParser();
    }

    protected function tearDown(): void
    {
        $dir = sys_get_temp_dir() . '/doctest';

        if (is_dir($dir)) {
            array_map(unlink(...), glob($dir . '/*.php') ?: []);
        }
    }

    private function makeBlock(string $code): CodeBlock
    {
        $parsed = $this->parser->parse($code);

        return new CodeBlock(
            file: 'test.md',
            startLine: 1,
            rawCode: $code,
            executableCode: $parsed->executableCode,
            attributes: new Attributes(),
            assertions: $parsed->assertions,
        );
    }

    #[Test]
    public function generates_concatenated_file_from_multiple_blocks(): void
    {
        $blocks = [
            $this->makeBlock('$counter = 0;'),
            $this->makeBlock("\$counter++;\n// Expect: \$counter === 1"),
        ];

        $filePath = $this->generator->generateGroup($blocks);

        $this->assertFileExists($filePath);
        $content = file_get_contents($filePath);
        $this->assertStringContainsString('$counter = 0;', $content);
        $this->assertStringContainsString('$counter++', $content);
    }

    #[Test]
    public function preserves_block_execution_order(): void
    {
        $blocks = [
            $this->makeBlock('$x = 1;'),
            $this->makeBlock('$x = 2;'),
            $this->makeBlock('$x = 3;'),
        ];

        $filePath = $this->generator->generateGroup($blocks);
        $content = file_get_contents($filePath);

        $pos1 = strpos($content, '$x = 1;');
        $pos2 = strpos($content, '$x = 2;');
        $pos3 = strpos($content, '$x = 3;');

        $this->assertLessThan($pos2, $pos1);
        $this->assertLessThan($pos3, $pos2);
    }

    #[Test]
    public function each_block_has_independent_assertion_instrumentation(): void
    {
        $blocks = [
            $this->makeBlock("echo \"a\";\n// Output: a"),
            $this->makeBlock("echo \"b\";\n// Output: b"),
        ];

        $filePath = $this->generator->generateGroup($blocks);
        $content = file_get_contents($filePath);

        $this->assertSame(2, substr_count($content, 'ob_start()'));
        $this->assertSame(2, substr_count($content, 'ob_get_clean()'));
    }

    #[Test]
    public function generates_single_stderr_json(): void
    {
        $blocks = [
            $this->makeBlock("echo \"a\";\n// Output: a"),
            $this->makeBlock("echo \"b\";\n// Output: b"),
        ];

        $filePath = $this->generator->generateGroup($blocks);
        $content = file_get_contents($filePath);

        $this->assertSame(1, substr_count($content, 'fwrite(STDERR'));
    }

    #[Test]
    public function generated_group_file_passes_syntax_check(): void
    {
        $blocks = [
            $this->makeBlock("\$x = 1;\n// Expect: \$x === 1"),
            $this->makeBlock("echo \"hello\";\n// Output: hello"),
        ];

        $filePath = $this->generator->generateGroup($blocks);

        $output = [];
        $exitCode = 0;
        exec(PHP_BINARY . ' -l ' . escapeshellarg($filePath) . ' 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, 'Generated group file has syntax errors: ' . implode("\n", $output));
    }
}
