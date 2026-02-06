<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Executor;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\CodeBlock\Attribute;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Executor\CodeGenerator;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\Assertion\ExpectAssertion;
use TestFlowLabs\DocTest\Assertion\OutputAssertion;
use TestFlowLabs\DocTest\Assertion\OutputJsonAssertion;
use TestFlowLabs\DocTest\Assertion\OutputMatchesAssertion;
use TestFlowLabs\DocTest\Assertion\OutputContainsAssertion;

final class CodeGeneratorTest extends TestCase
{
    private CodeGenerator $generator;
    private AssertionParser $assertionParser;

    protected function setUp(): void
    {
        $this->generator       = new CodeGenerator();
        $this->assertionParser = new AssertionParser();
    }

    protected function tearDown(): void
    {
        // Clean up generated files
        $dir = sys_get_temp_dir().'/doctest';

        if (is_dir($dir)) {
            array_map(unlink(...), glob($dir.'/*.php') ?: []);
        }
    }

    /**
     * @param  array<\TestFlowLabs\DocTest\Assertion\Assertion>  $assertions
     */
    private function makeBlock(string $code, ?Attribute $attribute = null, ?string $throwsClass = null, ?string $throwsMessage = null, array $assertions = [], ?string $group = null): CodeBlock
    {
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
                throwsMessage: $throwsMessage,
            ),
            assertions: $assertions,
        );
    }

    #[Test]
    public function generates_valid_php_file_for_simple_echo(): void
    {
        $block    = $this->makeBlock('echo "Hello World";');
        $filePath = $this->generator->generate($block);

        $this->assertFileExists($filePath);
        $this->assertStringStartsWith('<?php', file_get_contents($filePath));
    }

    #[Test]
    public function generates_output_capture_with_html_assertion(): void
    {
        $block = $this->makeBlock(
            'echo "Hello";',
            assertions: [new OutputAssertion('Hello', 1)],
        );
        $filePath = $this->generator->generate($block);
        $content  = file_get_contents($filePath);

        $this->assertStringContainsString('ob_start()', $content);
        $this->assertStringContainsString('ob_get_clean()', $content);
    }

    #[Test]
    public function generates_expect_assertion_evaluation(): void
    {
        $block = $this->makeBlock(
            '$x = 42;',
            assertions: [new ExpectAssertion('$x === 42', 2)],
        );
        $filePath = $this->generator->generate($block);
        $content  = file_get_contents($filePath);

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
        $content  = file_get_contents($filePath);

        $this->assertStringContainsString('try', $content);
        $this->assertStringContainsString('catch', $content);
    }

    #[Test]
    public function generates_raw_code_for_parse_error(): void
    {
        $block    = $this->makeBlock('$x = {invalid;', Attribute::ParseError);
        $filePath = $this->generator->generate($block);
        $content  = file_get_contents($filePath);

        // parse_error blocks get raw code without instrumentation
        $this->assertStringContainsString('$x = {invalid;', $content);
        $this->assertStringNotContainsString('ob_start()', $content);
    }

    #[Test]
    public function writes_results_to_stderr_as_json(): void
    {
        $block = $this->makeBlock(
            'echo "test";',
            assertions: [new OutputAssertion('test', 1)],
        );
        $filePath = $this->generator->generate($block);
        $content  = file_get_contents($filePath);

        $this->assertStringContainsString('STDERR', $content);
        $this->assertStringContainsString('json_encode', $content);
    }

    #[Test]
    public function handles_block_with_no_assertions(): void
    {
        $block    = $this->makeBlock('$x = 42;');
        $filePath = $this->generator->generate($block);
        $content  = file_get_contents($filePath);

        $this->assertStringContainsString('$x = 42;', $content);
        // No ob_start for blocks without output assertions
        $this->assertStringNotContainsString('ob_start()', $content);
    }

    #[Test]
    public function handles_mixed_output_and_expect(): void
    {
        $block = $this->makeBlock(
            "echo \"Hi\";\n\$x = 1;",
            assertions: [
                new OutputAssertion('Hi', 1),
                new ExpectAssertion('$x === 1', 2),
            ],
        );
        $filePath = $this->generator->generate($block);
        $content  = file_get_contents($filePath);

        $this->assertStringContainsString('ob_start()', $content);
        $this->assertStringContainsString('$x === 1', $content);
    }

    #[Test]
    public function generated_file_passes_syntax_check(): void
    {
        $block = $this->makeBlock(
            'echo "Hello";',
            assertions: [new OutputAssertion('Hello', 1)],
        );
        $filePath = $this->generator->generate($block);

        $output   = [];
        $exitCode = 0;
        exec(PHP_BINARY.' -l '.escapeshellarg($filePath).' 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, 'Generated PHP file has syntax errors: '.implode("\n", $output));
    }

    #[Test]
    public function generates_result_comment_capture(): void
    {
        $block    = $this->makeBlock('$x = 42; // => 42');
        $filePath = $this->generator->generate($block);
        $content  = file_get_contents($filePath);

        $this->assertStringContainsString('var_export', $content);
        $this->assertStringContainsString("'type' => 'result_comment'", $content);
        $this->assertStringContainsString('$x = 42', $content);
    }

    #[Test]
    public function result_comment_generated_code_passes_syntax_check(): void
    {
        $block    = $this->makeBlock('$x = 42; // => 42');
        $filePath = $this->generator->generate($block);

        $output   = [];
        $exitCode = 0;
        exec(PHP_BINARY.' -l '.escapeshellarg($filePath).' 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, 'Generated PHP file has syntax errors: '.implode("\n", $output));
    }

    #[Test]
    public function generates_multiple_result_comment_captures(): void
    {
        $block    = $this->makeBlock("\$x = 1; // => 1\n\$y = 2; // => 2");
        $filePath = $this->generator->generate($block);
        $content  = file_get_contents($filePath);

        $this->assertSame(2, substr_count($content, "'type' => 'result_comment'"));
    }

    #[Test]
    public function result_comment_produces_correct_output_when_executed(): void
    {
        $block    = $this->makeBlock('$x = 42; // => 42');
        $filePath = $this->generator->generate($block);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open([PHP_BINARY, $filePath], $descriptors, $pipes);
        $stderr  = stream_get_contents($pipes[2]);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $results = json_decode($stderr, true);
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertSame('result_comment', $results[0]['type']);
        $this->assertSame('42', $results[0]['expected']);
        $this->assertSame('42', $results[0]['actual']);
    }

    #[Test]
    public function result_comment_mixed_with_html_output_assertion(): void
    {
        $block = $this->makeBlock(
            "\$x = 42; // => 42\necho \$x;",
            assertions: [new OutputAssertion('42', 3)],
        );
        $filePath = $this->generator->generate($block);
        $content  = file_get_contents($filePath);

        $this->assertStringContainsString("'type' => 'result_comment'", $content);
        $this->assertStringContainsString('ob_start()', $content);
    }

    // --- Group generation ---

    #[Test]
    public function generates_group_file_with_multiple_blocks(): void
    {
        $blocks = [
            $this->makeBlock('$x = 1;', group: 'grp'),
            $this->makeBlock('echo $x;', group: 'grp', assertions: [new OutputAssertion('1', 1)]),
        ];
        $filePath = $this->generator->generateGroup($blocks);
        $content  = file_get_contents($filePath);

        $this->assertFileExists($filePath);
        $this->assertStringContainsString('$__doctest_results', $content);
        $this->assertStringContainsString('$x = 1;', $content);
        $this->assertStringContainsString('ob_start()', $content);
    }

    #[Test]
    public function group_file_passes_syntax_check(): void
    {
        $blocks = [
            $this->makeBlock('$x = 42;', group: 'grp'),
            $this->makeBlock('$y = $x + 1; // => 43', group: 'grp'),
        ];
        $filePath = $this->generator->generateGroup($blocks);

        $output   = [];
        $exitCode = 0;
        exec(PHP_BINARY.' -l '.escapeshellarg($filePath).' 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, 'Generated group PHP file has syntax errors: '.implode("\n", $output));
    }

    #[Test]
    public function group_without_assertions_has_no_ob_start(): void
    {
        $blocks = [
            $this->makeBlock('$x = 1;', group: 'grp'),
            $this->makeBlock('$y = 2;', group: 'grp'),
        ];
        $filePath = $this->generator->generateGroup($blocks);
        $content  = file_get_contents($filePath);

        $this->assertStringNotContainsString('ob_start()', $content);
    }

    // --- Setup/teardown ---

    #[Test]
    public function generates_setup_code_in_single_block(): void
    {
        $block    = $this->makeBlock('echo $setup_var;', assertions: [new OutputAssertion('hello', 1)]);
        $filePath = $this->generator->generate($block, setup: '$setup_var = "hello";');
        $content  = file_get_contents($filePath);

        $this->assertStringContainsString('$setup_var = "hello"', $content);
    }

    #[Test]
    public function generates_teardown_code_in_single_block(): void
    {
        $block    = $this->makeBlock('$x = 1;');
        $filePath = $this->generator->generate($block, teardown: '// teardown marker');
        $content  = file_get_contents($filePath);

        $this->assertStringContainsString('// teardown marker', $content);
    }

    #[Test]
    public function generates_setup_and_teardown_in_group(): void
    {
        $blocks = [
            $this->makeBlock('echo $s;', group: 'grp', assertions: [new OutputAssertion('ok', 1)]),
        ];
        $filePath = $this->generator->generateGroup($blocks, setup: '$s = "ok";', teardown: '// cleanup');
        $content  = file_get_contents($filePath);

        $this->assertStringContainsString('$s = "ok"', $content);
        $this->assertStringContainsString('// cleanup', $content);
    }

    // --- Assertion type generation ---

    #[Test]
    public function generates_output_contains_assertion(): void
    {
        $block = $this->makeBlock(
            'echo "Hello World";',
            assertions: [new OutputContainsAssertion('World', 1)],
        );
        $filePath = $this->generator->generate($block);
        $content  = file_get_contents($filePath);

        $this->assertStringContainsString("'type' => 'output_contains'", $content);
    }

    #[Test]
    public function generates_output_matches_assertion(): void
    {
        $block = $this->makeBlock(
            'echo "abc123";',
            assertions: [new OutputMatchesAssertion('/^\w+$/', 1)],
        );
        $filePath = $this->generator->generate($block);
        $content  = file_get_contents($filePath);

        $this->assertStringContainsString("'type' => 'output_matches'", $content);
    }

    #[Test]
    public function generates_output_json_assertion(): void
    {
        $block = $this->makeBlock(
            'echo json_encode(["a" => 1]);',
            assertions: [new OutputJsonAssertion('{"a":1}', 1)],
        );
        $filePath = $this->generator->generate($block);
        $content  = file_get_contents($filePath);

        $this->assertStringContainsString("'type' => 'output_json'", $content);
    }

    // --- Throws wrapper ---

    #[Test]
    public function throws_wrapper_indents_multiline_code(): void
    {
        $block = $this->makeBlock(
            "\$x = 1;\n\$y = 2;\nthrow new \\RuntimeException(\"err\");",
            Attribute::Throws,
            'RuntimeException',
        );
        $filePath = $this->generator->generate($block);
        $content  = file_get_contents($filePath);

        $this->assertStringContainsString('try {', $content);
        $this->assertStringContainsString('    $x = 1;', $content);
        $this->assertStringContainsString('    $y = 2;', $content);
    }

    #[Test]
    public function throws_wrapper_writes_json_on_no_exception(): void
    {
        $block = $this->makeBlock(
            '$x = 1;',
            Attribute::Throws,
            'RuntimeException',
        );
        $filePath = $this->generator->generate($block);
        $content  = file_get_contents($filePath);

        $this->assertStringContainsString("'thrown' => false", $content);
        $this->assertStringContainsString("'thrown' => true", $content);
    }
}
