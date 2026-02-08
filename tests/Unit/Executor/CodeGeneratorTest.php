<?php

declare(strict_types=1);
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

beforeEach(function (): void {
    $this->generator       = new CodeGenerator();
    $this->assertionParser = new AssertionParser();

    /*
     * @param  array<\TestFlowLabs\DocTest\Assertion\Assertion>  $assertions
     */
    $this->makeBlock = function (string $code, ?Attribute $attribute = null, ?string $throwsClass = null, ?string $throwsMessage = null, array $assertions = [], ?string $group = null): CodeBlock {
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
    };
});
test('generates valid php file for simple echo', function (): void {
    $block    = ($this->makeBlock)('echo "Hello World";');
    $filePath = $this->generator->generate($block);

    expect($filePath)->toBeFile();
    expect(file_get_contents($filePath))->toStartWith('<?php');
});
test('generates output capture with html assertion', function (): void {
    $block    = ($this->makeBlock)('echo "Hello";', assertions: [new OutputAssertion('Hello', 1)]);
    $filePath = $this->generator->generate($block);
    $content  = file_get_contents($filePath);

    $this->assertStringContainsString('ob_start()', $content);
    $this->assertStringContainsString('ob_get_clean()', $content);
});
test('generates expect assertion evaluation', function (): void {
    $block    = ($this->makeBlock)('$x = 42;', assertions: [new ExpectAssertion('$x === 42', 2)]);
    $filePath = $this->generator->generate($block);
    $content  = file_get_contents($filePath);

    $this->assertStringContainsString('$x === 42', $content);
});
test('generates throws wrapper', function (): void {
    $block    = ($this->makeBlock)('throw new RuntimeException("test");', Attribute::Throws, 'RuntimeException', 'test');
    $filePath = $this->generator->generate($block);
    $content  = file_get_contents($filePath);

    $this->assertStringContainsString('try', $content);
    $this->assertStringContainsString('catch', $content);
});
test('generates raw code for parse error', function (): void {
    $block    = ($this->makeBlock)('$x = {invalid;', Attribute::ParseError);
    $filePath = $this->generator->generate($block);
    $content  = file_get_contents($filePath);

    // parse_error blocks get raw code without instrumentation
    $this->assertStringContainsString('$x = {invalid;', $content);
    $this->assertStringNotContainsString('ob_start()', $content);
});
test('writes results to stderr as json', function (): void {
    $block    = ($this->makeBlock)('echo "test";', assertions: [new OutputAssertion('test', 1)]);
    $filePath = $this->generator->generate($block);
    $content  = file_get_contents($filePath);

    $this->assertStringContainsString('STDERR', $content);
    $this->assertStringContainsString('json_encode', $content);
});
test('handles block with no assertions', function (): void {
    $block    = ($this->makeBlock)('$x = 42;');
    $filePath = $this->generator->generate($block);
    $content  = file_get_contents($filePath);

    $this->assertStringContainsString('$x = 42;', $content);

    // No ob_start for blocks without output assertions
    $this->assertStringNotContainsString('ob_start()', $content);
});
test('handles mixed output and expect', function (): void {
    $block = ($this->makeBlock)("echo \"Hi\";\n\$x = 1;", assertions: [
        new OutputAssertion('Hi', 1),
        new ExpectAssertion('$x === 1', 2),
    ]);
    $filePath = $this->generator->generate($block);
    $content  = file_get_contents($filePath);

    $this->assertStringContainsString('ob_start()', $content);
    $this->assertStringContainsString('$x === 1', $content);
});
test('generated file passes syntax check', function (): void {
    $block    = ($this->makeBlock)('echo "Hello";', assertions: [new OutputAssertion('Hello', 1)]);
    $filePath = $this->generator->generate($block);

    $output   = [];
    $exitCode = 0;
    exec(PHP_BINARY.' -l '.escapeshellarg((string) $filePath).' 2>&1', $output, $exitCode);

    expect($exitCode)->toBe(0, 'Generated PHP file has syntax errors: '.implode("\n", $output));
});
test('generates result comment capture', function (): void {
    $block    = ($this->makeBlock)('$x = 42; // => 42');
    $filePath = $this->generator->generate($block);
    $content  = file_get_contents($filePath);

    $this->assertStringContainsString('var_export', $content);
    $this->assertStringContainsString("'type' => 'result_comment'", $content);
    $this->assertStringContainsString('$x = 42', $content);
});
test('result comment generated code passes syntax check', function (): void {
    $block    = ($this->makeBlock)('$x = 42; // => 42');
    $filePath = $this->generator->generate($block);

    $output   = [];
    $exitCode = 0;
    exec(PHP_BINARY.' -l '.escapeshellarg((string) $filePath).' 2>&1', $output, $exitCode);

    expect($exitCode)->toBe(0, 'Generated PHP file has syntax errors: '.implode("\n", $output));
});
test('generates multiple result comment captures', function (): void {
    $block    = ($this->makeBlock)("\$x = 1; // => 1\n\$y = 2; // => 2");
    $filePath = $this->generator->generate($block);
    $content  = file_get_contents($filePath);

    expect(substr_count($content, "'type' => 'result_comment'"))->toBe(2);
});
test('result comment produces correct output when executed', function (): void {
    $block    = ($this->makeBlock)('$x = 42; // => 42');
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
    expect($results)->toBeArray();
    expect($results)->toHaveCount(1);
    expect($results[0]['type'])->toBe('result_comment');
    expect($results[0]['expected'])->toBe('42');
    expect($results[0]['actual'])->toBe('42');
});
test('result comment mixed with html output assertion', function (): void {
    $block    = ($this->makeBlock)("\$x = 42; // => 42\necho \$x;", assertions: [new OutputAssertion('42', 3)]);
    $filePath = $this->generator->generate($block);
    $content  = file_get_contents($filePath);

    $this->assertStringContainsString("'type' => 'result_comment'", $content);
    $this->assertStringContainsString('ob_start()', $content);
});
test('generates group file with multiple blocks', function (): void {
    $blocks = [
        ($this->makeBlock)('$x = 1;', group: 'grp'),
        ($this->makeBlock)('echo $x;', group: 'grp', assertions: [new OutputAssertion('1', 1)]),
    ];
    $filePath = $this->generator->generateGroup($blocks);
    $content  = file_get_contents($filePath);

    expect($filePath)->toBeFile();
    $this->assertStringContainsString('$__doctest_results', $content);
    $this->assertStringContainsString('$x = 1;', $content);
    $this->assertStringContainsString('ob_start()', $content);
});
test('group file passes syntax check', function (): void {
    $blocks = [
        ($this->makeBlock)('$x = 42;', group: 'grp'),
        ($this->makeBlock)('$y = $x + 1; // => 43', group: 'grp'),
    ];
    $filePath = $this->generator->generateGroup($blocks);

    $output   = [];
    $exitCode = 0;
    exec(PHP_BINARY.' -l '.escapeshellarg((string) $filePath).' 2>&1', $output, $exitCode);

    expect($exitCode)->toBe(0, 'Generated group PHP file has syntax errors: '.implode("\n", $output));
});
test('group without assertions has no ob start', function (): void {
    $blocks = [
        ($this->makeBlock)('$x = 1;', group: 'grp'),
        ($this->makeBlock)('$y = 2;', group: 'grp'),
    ];
    $filePath = $this->generator->generateGroup($blocks);
    $content  = file_get_contents($filePath);

    $this->assertStringNotContainsString('ob_start()', $content);
});
test('generates setup code in single block', function (): void {
    $block    = ($this->makeBlock)('echo $setup_var;', assertions: [new OutputAssertion('hello', 1)]);
    $filePath = $this->generator->generate($block, setup: '$setup_var = "hello";');
    $content  = file_get_contents($filePath);

    $this->assertStringContainsString('$setup_var = "hello"', $content);
});
test('generates teardown code in single block', function (): void {
    $block    = ($this->makeBlock)('$x = 1;');
    $filePath = $this->generator->generate($block, teardown: '// teardown marker');
    $content  = file_get_contents($filePath);

    $this->assertStringContainsString('// teardown marker', $content);
});
test('generates setup and teardown in group', function (): void {
    $blocks = [
        ($this->makeBlock)('echo $s;', group: 'grp', assertions: [new OutputAssertion('ok', 1)]),
    ];
    $filePath = $this->generator->generateGroup($blocks, setup: '$s = "ok";', teardown: '// cleanup');
    $content  = file_get_contents($filePath);

    $this->assertStringContainsString('$s = "ok"', $content);
    $this->assertStringContainsString('// cleanup', $content);
});
test('generates output contains assertion', function (): void {
    $block    = ($this->makeBlock)('echo "Hello World";', assertions: [new OutputContainsAssertion('World', 1)]);
    $filePath = $this->generator->generate($block);
    $content  = file_get_contents($filePath);

    $this->assertStringContainsString("'type' => 'output_contains'", $content);
});
test('generates output matches assertion', function (): void {
    $block    = ($this->makeBlock)('echo "abc123";', assertions: [new OutputMatchesAssertion('/^\w+$/', 1)]);
    $filePath = $this->generator->generate($block);
    $content  = file_get_contents($filePath);

    $this->assertStringContainsString("'type' => 'output_matches'", $content);
});
test('generates output json assertion', function (): void {
    $block    = ($this->makeBlock)('echo json_encode(["a" => 1]);', assertions: [new OutputJsonAssertion('{"a":1}', 1)]);
    $filePath = $this->generator->generate($block);
    $content  = file_get_contents($filePath);

    $this->assertStringContainsString("'type' => 'output_json'", $content);
});
test('throws wrapper indents multiline code', function (): void {
    $block    = ($this->makeBlock)("\$x = 1;\n\$y = 2;\nthrow new \\RuntimeException(\"err\");", Attribute::Throws, 'RuntimeException');
    $filePath = $this->generator->generate($block);
    $content  = file_get_contents($filePath);

    $this->assertStringContainsString('try {', $content);
    $this->assertStringContainsString('    $x = 1;', $content);
    $this->assertStringContainsString('    $y = 2;', $content);
});
test('throws wrapper writes json on no exception', function (): void {
    $block    = ($this->makeBlock)('$x = 1;', Attribute::Throws, 'RuntimeException');
    $filePath = $this->generator->generate($block);
    $content  = file_get_contents($filePath);

    $this->assertStringContainsString("'thrown' => false", $content);
    $this->assertStringContainsString("'thrown' => true", $content);
});
test('bootstrap code injected in normal block', function (): void {
    $generator = new CodeGenerator("require_once '/tmp/bootstrap.php';");
    $block     = ($this->makeBlock)('echo "test";');
    $filePath  = $generator->generate($block);
    $content   = file_get_contents($filePath);

    $this->assertStringContainsString("require_once '/tmp/bootstrap.php';", $content);
    expect($content)->toStartWith("<?php\n");
});
test('bootstrap code injected in group', function (): void {
    $generator = new CodeGenerator("require_once '/tmp/bootstrap.php';");
    $blocks    = [
        ($this->makeBlock)('$x = 1;', group: 'grp'),
        ($this->makeBlock)('echo $x;', group: 'grp'),
    ];
    $filePath = $generator->generateGroup($blocks);
    $content  = file_get_contents($filePath);

    $this->assertStringContainsString("require_once '/tmp/bootstrap.php';", $content);
});
test('bootstrap code injected in parse error block', function (): void {
    $generator = new CodeGenerator("require_once '/tmp/bootstrap.php';");
    $block     = ($this->makeBlock)('invalid syntax here %%%', Attribute::ParseError);
    $filePath  = $generator->generate($block);
    $content   = file_get_contents($filePath);

    $this->assertStringContainsString("require_once '/tmp/bootstrap.php';", $content);
});
test('no bootstrap when null', function (): void {
    $generator = new CodeGenerator(null);
    $block     = ($this->makeBlock)('echo "test";');
    $filePath  = $generator->generate($block);
    $content   = file_get_contents($filePath);

    $this->assertStringNotContainsString('require_once', $content);
});
test('block bootstrap code injected after global bootstrap', function (): void {
    $generator = new CodeGenerator("require_once '/global.php';");
    $block     = ($this->makeBlock)('echo "test";');
    $filePath  = $generator->generate($block, blockBootstrapCode: "require_once '/profile.php';");
    $content   = file_get_contents($filePath);

    $globalPos  = strpos($content, '/global.php');
    $profilePos = strpos($content, '/profile.php');

    expect($globalPos)->toBeLessThan($profilePos);
});
test('block bootstrap code without global bootstrap works', function (): void {
    $generator = new CodeGenerator();
    $block     = ($this->makeBlock)('echo "test";');
    $filePath  = $generator->generate($block, blockBootstrapCode: "require_once '/profile.php';");
    $content   = file_get_contents($filePath);

    $this->assertStringContainsString("require_once '/profile.php';", $content);
});
test('block bootstrap code null does not add anything', function (): void {
    $generator = new CodeGenerator();
    $block     = ($this->makeBlock)('echo "test";');
    $filePath  = $generator->generate($block, blockBootstrapCode: null);
    $content   = file_get_contents($filePath);

    $this->assertStringNotContainsString('require_once', $content);
});
test('block bootstrap in group injected correctly', function (): void {
    $generator = new CodeGenerator("require_once '/global.php';");
    $blocks    = [
        ($this->makeBlock)('$x = 1;', group: 'grp'),
        ($this->makeBlock)('echo $x;', group: 'grp'),
    ];
    $filePath = $generator->generateGroup($blocks, blockBootstrapCode: "require_once '/profile.php';");
    $content  = file_get_contents($filePath);

    $globalPos  = strpos($content, '/global.php');
    $profilePos = strpos($content, '/profile.php');

    expect($globalPos)->toBeLessThan($profilePos);
});
test('block bootstrap in parse error block', function (): void {
    $generator = new CodeGenerator("require_once '/global.php';");
    $block     = ($this->makeBlock)('$x = {invalid;', Attribute::ParseError);
    $filePath  = $generator->generate($block, blockBootstrapCode: "require_once '/profile.php';");
    $content   = file_get_contents($filePath);

    $this->assertStringContainsString('/global.php', $content);
    $this->assertStringContainsString('/profile.php', $content);
});
test('block bootstrap generated file passes syntax check', function (): void {
    $generator = new CodeGenerator('// global bootstrap');
    $block     = ($this->makeBlock)('echo "Hello";', assertions: [new OutputAssertion('Hello', 1)]);
    $filePath  = $generator->generate($block, blockBootstrapCode: '// block bootstrap');

    $output   = [];
    $exitCode = 0;
    exec(PHP_BINARY.' -l '.escapeshellarg((string) $filePath).' 2>&1', $output, $exitCode);

    expect($exitCode)->toBe(0, 'Generated PHP file has syntax errors: '.implode("\n", $output));
});
test('generates debug dump code for dd marker', function (): void {
    $block    = ($this->makeBlock)('$x = 42; // => dd()');
    $filePath = $this->generator->generate($block);
    $content  = file_get_contents($filePath);

    $this->assertStringContainsString("'type' => 'debug'", $content);
    $this->assertStringContainsString('var_export', $content);
    $this->assertStringContainsString('$x = 42', $content);
});
test('debug dump generated code passes syntax check', function (): void {
    $block    = ($this->makeBlock)('$x = 42; // => dd()');
    $filePath = $this->generator->generate($block);

    $output   = [];
    $exitCode = 0;
    exec(PHP_BINARY.' -l '.escapeshellarg((string) $filePath).' 2>&1', $output, $exitCode);

    expect($exitCode)->toBe(0, 'Generated PHP file has syntax errors: '.implode("\n", $output));
});
test('debug dump produces correct output when executed', function (): void {
    $block    = ($this->makeBlock)('$x = 42; // => dd()');
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
    expect($results)->toBeArray();
    expect($results)->toHaveCount(1);
    expect($results[0]['type'])->toBe('debug');
    expect($results[0]['expression'])->toBe('$x = 42');
    expect($results[0]['value'])->toBe('42');
    expect($results[0]['line'])->toBe(1);
});
test('generates multiple debug dumps', function (): void {
    $block    = ($this->makeBlock)("\$x = 1; // => dd()\n\$y = 2; // => dd()");
    $filePath = $this->generator->generate($block);
    $content  = file_get_contents($filePath);

    expect(substr_count($content, "'type' => 'debug'"))->toBe(2);
});
test('debug dump mixed with result comment', function (): void {
    $block    = ($this->makeBlock)("\$x = 42; // => dd()\n\$y = 10; // => 10");
    $filePath = $this->generator->generate($block);
    $content  = file_get_contents($filePath);

    $this->assertStringContainsString("'type' => 'debug'", $content);
    $this->assertStringContainsString("'type' => 'result_comment'", $content);
});
test('debug dump in group generates correct code', function (): void {
    $blocks = [
        ($this->makeBlock)('$x = 42; // => dd()', group: 'grp'),
        ($this->makeBlock)('$y = $x + 1; // => 43', group: 'grp'),
    ];
    $filePath = $this->generator->generateGroup($blocks);
    $content  = file_get_contents($filePath);

    $this->assertStringContainsString("'type' => 'debug'", $content);
    $this->assertStringContainsString("'type' => 'result_comment'", $content);
});
test('debug dump in group passes syntax check', function (): void {
    $blocks = [
        ($this->makeBlock)('$x = 42; // => dd()', group: 'grp'),
    ];
    $filePath = $this->generator->generateGroup($blocks);

    $output   = [];
    $exitCode = 0;
    exec(PHP_BINARY.' -l '.escapeshellarg((string) $filePath).' 2>&1', $output, $exitCode);

    expect($exitCode)->toBe(0, 'Generated group PHP file has syntax errors: '.implode("\n", $output));
});
