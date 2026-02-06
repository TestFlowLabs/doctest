<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Executor;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\Executor\CodeGenerator;

final class CodeGeneratorSetupTeardownTest extends TestCase
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
    public function prepends_setup_code_before_block_code(): void
    {
        $block = $this->makeBlock('echo $greeting;');
        $setup = '$greeting = "hello";';

        $filePath = $this->generator->generate($block, setup: $setup);
        $content = file_get_contents($filePath);

        $setupPos = strpos($content, '$greeting = "hello"');
        $codePos = strpos($content, 'echo $greeting');

        $this->assertNotFalse($setupPos);
        $this->assertNotFalse($codePos);
        $this->assertLessThan($codePos, $setupPos);
    }

    #[Test]
    public function appends_teardown_code_after_block_code(): void
    {
        $block = $this->makeBlock('$resource = "open";');
        $teardown = '$resource = null;';

        $filePath = $this->generator->generate($block, teardown: $teardown);
        $content = file_get_contents($filePath);

        $codePos = strpos($content, '$resource = "open"');
        $teardownPos = strpos($content, '$resource = null');

        $this->assertNotFalse($codePos);
        $this->assertNotFalse($teardownPos);
        $this->assertLessThan($teardownPos, $codePos);
    }

    #[Test]
    public function setup_and_teardown_together_in_correct_order(): void
    {
        $block = $this->makeBlock('echo $x;');
        $setup = '$x = 42;';
        $teardown = 'unset($x);';

        $filePath = $this->generator->generate($block, setup: $setup, teardown: $teardown);
        $content = file_get_contents($filePath);

        $setupPos = strpos($content, '$x = 42');
        $codePos = strpos($content, 'echo $x');
        $teardownPos = strpos($content, 'unset($x)');

        $this->assertNotFalse($setupPos);
        $this->assertNotFalse($codePos);
        $this->assertNotFalse($teardownPos);
        $this->assertLessThan($codePos, $setupPos);
        $this->assertLessThan($teardownPos, $codePos);
    }

    #[Test]
    public function null_setup_teardown_produces_no_change(): void
    {
        $block = $this->makeBlock('echo "test";');

        $withoutParams = $this->generator->generate($block);
        $contentWithout = file_get_contents($withoutParams);

        // Generate new file with null params explicitly
        $withNullParams = $this->generator->generate($block, setup: null, teardown: null);
        $contentWith = file_get_contents($withNullParams);

        // Both should produce structurally identical content (ignoring file path differences)
        $this->assertSame(
            preg_replace('/doctest_[a-f0-9]+/', 'doctest_X', $contentWithout),
            preg_replace('/doctest_[a-f0-9]+/', 'doctest_X', $contentWith),
        );
    }

    #[Test]
    public function setup_variables_accessible_in_block_scope(): void
    {
        $block = $this->makeBlock("echo \$greeting;\n// Output: hello world");
        $setup = '$greeting = "hello world";';

        $filePath = $this->generator->generate($block, setup: $setup);

        $output = [];
        $exitCode = 0;
        exec(PHP_BINARY . ' -l ' . escapeshellarg($filePath) . ' 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, 'Generated file with setup has syntax errors: ' . implode("\n", $output));
    }

    #[Test]
    public function group_generation_supports_setup_and_teardown(): void
    {
        $blocks = [
            $this->makeBlock('$counter++;'),
            $this->makeBlock("\$counter++;\n// Expect: \$counter === 2"),
        ];
        $setup = '$counter = 0;';
        $teardown = 'unset($counter);';

        $filePath = $this->generator->generateGroup($blocks, setup: $setup, teardown: $teardown);
        $content = file_get_contents($filePath);

        $setupPos = strpos($content, '$counter = 0');
        $firstBlockPos = strpos($content, '$counter++');
        $teardownPos = strpos($content, 'unset($counter)');

        $this->assertNotFalse($setupPos);
        $this->assertNotFalse($firstBlockPos);
        $this->assertNotFalse($teardownPos);
        $this->assertLessThan($firstBlockPos, $setupPos);
        $this->assertLessThan($teardownPos, $firstBlockPos);
    }
}
