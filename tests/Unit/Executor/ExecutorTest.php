<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Executor\Executor;
use TestFlowLabs\DocTest\CodeBlock\Attribute;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\Executor\ExecutionResult;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\Assertion\ExpectAssertion;
use TestFlowLabs\DocTest\Assertion\OutputAssertion;
use TestFlowLabs\DocTest\Assertion\OutputJsonAssertion;
use TestFlowLabs\DocTest\Assertion\OutputMatchesAssertion;
use TestFlowLabs\DocTest\Assertion\OutputContainsAssertion;

beforeEach(function (): void {
    $this->executor        = new Executor();
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
test('executes simple echo and captures output', function (): void {
    $block  = ($this->makeBlock)('echo "Hello World";', assertions: [new OutputAssertion('Hello World', 1)]);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
    expect($result->actualOutput)->toBe('Hello World');
});
test('executes code with no assertions as smoke test', function (): void {
    $block  = ($this->makeBlock)('$x = 42;');
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
});
test('returns skipped result for ignore attribute', function (): void {
    $block  = ($this->makeBlock)('echo "ignored";', Attribute::Ignore);
    $result = $this->executor->execute($block);

    expect($result->skipped)->toBeTrue();
    expect($result->passed)->toBeTrue();
});
test('syntax checks no run blocks', function (): void {
    $block  = ($this->makeBlock)('$x = 1 + 2;', Attribute::NoRun);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
});
test('detects parse errors for parse error blocks', function (): void {
    $block  = ($this->makeBlock)('$x = {invalid syntax;', Attribute::ParseError);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
});
test('captures exceptions via throws attribute', function (): void {
    $block  = ($this->makeBlock)('throw new \RuntimeException("test error");', Attribute::Throws, 'RuntimeException');
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
});
test('throws attribute fails when no exception thrown', function (): void {
    $block  = ($this->makeBlock)('$x = 1;', Attribute::Throws, 'RuntimeException');
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeFalse();
});
test('evaluates expect expressions', function (): void {
    $block  = ($this->makeBlock)('$x = 42;', assertions: [new ExpectAssertion('$x === 42', 2)]);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
});
test('expect with falsy result fails', function (): void {
    $block  = ($this->makeBlock)('$x = 42;', assertions: [new ExpectAssertion('$x === 99', 2)]);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeFalse();
});
test('output assertion with combined output', function (): void {
    $block  = ($this->makeBlock)("echo \"a\";\necho \"b\";", assertions: [new OutputAssertion('ab', 1)]);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
});
test('handles process timeout gracefully', function (): void {
    $executor = new Executor(timeout: 1);
    $block    = ($this->makeBlock)('sleep(10); echo "done";');
    $result   = $executor->execute($block);

    expect($result->passed)->toBeFalse();
    expect($result->error)->not->toBeNull();
});
test('output assertion failure reports diff', function (): void {
    $block  = ($this->makeBlock)('echo "wrong";', assertions: [new OutputAssertion('right', 2)]);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeFalse();
    expect($result->actualOutput)->toBe('wrong');
    expect($result->expectedOutput)->toBe('right');
});
test('evaluates result comment with matching value', function (): void {
    $block  = ($this->makeBlock)('$x = 42; // => 42');
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
});
test('evaluates result comment with boolean', function (): void {
    $block  = ($this->makeBlock)('$x = true; // => true');
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
});
test('evaluates result comment with null', function (): void {
    $block  = ($this->makeBlock)('$x = null; // => NULL');
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
});
test('result comment fails on mismatch', function (): void {
    $block  = ($this->makeBlock)('$x = 42; // => 99');
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeFalse();
    expect($result->error)->not->toBeNull();
    $this->assertStringContainsString('result_comment', $result->error);
});
test('evaluates multiple result comments', function (): void {
    $block  = ($this->makeBlock)("\$x = 1; // => 1\n\$y = 2; // => 2\n\$z = \$x + \$y; // => 3");
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
});
test('result comment mixed with html output assertion', function (): void {
    $block  = ($this->makeBlock)("\$x = 42; // => 42\necho \$x;", assertions: [new OutputAssertion('42', 3)]);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
});
test('populates assertion details for output', function (): void {
    $block  = ($this->makeBlock)('echo "Hello";', assertions: [new OutputAssertion('Hello', 2)]);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
    expect($result->assertionDetails)->toHaveCount(1);
    expect($result->assertionDetails[0]->type)->toBe('output');
    expect($result->assertionDetails[0]->passed)->toBeTrue();
    expect($result->assertionDetails[0]->expected)->toBe('Hello');
    expect($result->assertionDetails[0]->actual)->toBe('Hello');
});
test('populates assertion details for result comment', function (): void {
    $block  = ($this->makeBlock)('$x = 42; // => 42');
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
    expect($result->assertionDetails)->toHaveCount(1);
    expect($result->assertionDetails[0]->type)->toBe('result_comment');
    expect($result->assertionDetails[0]->passed)->toBeTrue();
    expect($result->assertionDetails[0]->expected)->toBe('42');
    expect($result->assertionDetails[0]->expression)->toBe('$x = 42');
});
test('populates assertion details for expect', function (): void {
    $block  = ($this->makeBlock)('$x = 42;', assertions: [new ExpectAssertion('$x === 42', 2)]);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
    expect($result->assertionDetails)->toHaveCount(1);
    expect($result->assertionDetails[0]->type)->toBe('expect');
    expect($result->assertionDetails[0]->passed)->toBeTrue();
});
test('populates assertion details on failure', function (): void {
    $block  = ($this->makeBlock)("\$x = 1; // => 1\n\$y = 2; // => 99");
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeFalse();
    expect($result->assertionDetails)->toHaveCount(2);
    expect($result->assertionDetails[0]->passed)->toBeTrue();
    expect($result->assertionDetails[1]->passed)->toBeFalse();
});
test('populates multiple assertion details', function (): void {
    $block = ($this->makeBlock)("echo \"a\";\necho \"b\";", assertions: [
        new OutputAssertion('ab', 3),
    ]);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
    expect($result->assertionDetails)->toHaveCount(1);
    expect($result->assertionDetails[0]->expected)->toBe('ab');
});
test('execute all calls on result callback for each block', function (): void {
    $blocks = [
        ($this->makeBlock)('echo "a";', assertions: [new OutputAssertion('a', 1)]),
        ($this->makeBlock)('$x = 42;'),
        ($this->makeBlock)('echo "b";', assertions: [new OutputAssertion('b', 1)]),
    ];

    /** @var array<ExecutionResult> $callbackResults */
    $callbackResults = [];
    $results         = $this->executor->executeAll($blocks, onResult: function (ExecutionResult $result) use (&$callbackResults): void {
        $callbackResults[] = $result;
    });

    expect($results)->toHaveCount(3);
    expect($callbackResults)->toHaveCount(3);
    expect($callbackResults)->toBe($results);
});
test('execute all streams results in execution order', function (): void {
    $blocks = [
        ($this->makeBlock)('echo "first";', assertions: [new OutputAssertion('first', 1)]),
        ($this->makeBlock)('echo "second";', assertions: [new OutputAssertion('second', 1)]),
    ];

    /** @var array<string> $order */
    $order = [];
    $this->executor->executeAll($blocks, onResult: function (ExecutionResult $result) use (&$order): void {
        $order[] = $result->actualOutput ?? 'no-output';
    });

    expect($order)->toBe(['first', 'second']);
});
test('execute all without callback still returns results', function (): void {
    $blocks = [
        ($this->makeBlock)('echo "a";', assertions: [new OutputAssertion('a', 1)]),
        ($this->makeBlock)('$x = 1;'),
    ];

    $results = $this->executor->executeAll($blocks);

    expect($results)->toHaveCount(2);
    expect($results[0]->passed)->toBeTrue();
    expect($results[1]->passed)->toBeTrue();
});
test('execute all stops normal blocks when callback returns false', function (): void {
    $blocks = [
        ($this->makeBlock)('echo "a";', assertions: [new OutputAssertion('wrong', 1)]),
        ($this->makeBlock)('echo "b";', assertions: [new OutputAssertion('b', 1)]),
        ($this->makeBlock)('echo "c";', assertions: [new OutputAssertion('c', 1)]),
    ];

    /** @var array<ExecutionResult> $collected */
    $collected = [];
    $results   = $this->executor->executeAll($blocks, onResult: function (ExecutionResult $result) use (&$collected): ?bool {
        $collected[] = $result;

        return $result->passed ? null : false;
    });

    expect($results)->toHaveCount(1);
    expect($collected)->toHaveCount(1);
    expect($results[0]->passed)->toBeFalse();
});
test('execute all stops grouped blocks when callback returns false', function (): void {
    $blocks = [
        ($this->makeBlock)('echo "wrong";', group: 'grp', assertions: [new OutputAssertion('nope', 1)]),
        ($this->makeBlock)('echo "b";', group: 'grp', assertions: [new OutputAssertion('b', 1)]),
    ];

    /** @var array<ExecutionResult> $collected */
    $collected = [];
    $results   = $this->executor->executeAll($blocks, onResult: function (ExecutionResult $result) use (&$collected): ?bool {
        $collected[] = $result;

        return $result->passed ? null : false;
    });

    expect($collected)->toHaveCount(1);
    expect($collected[0]->passed)->toBeFalse();
});
test('execute all skips groups when stopped during normal blocks', function (): void {
    $blocks = [
        ($this->makeBlock)('echo "wrong";', assertions: [new OutputAssertion('nope', 1)]),
        ($this->makeBlock)('echo "grouped";', group: 'grp', assertions: [new OutputAssertion('grouped', 1)]),
    ];

    /** @var array<ExecutionResult> $collected */
    $collected = [];
    $this->executor->executeAll($blocks, onResult: function (ExecutionResult $result) use (&$collected): ?bool {
        $collected[] = $result;

        return $result->passed ? null : false;
    });

    expect($collected)->toHaveCount(1);
    expect($collected[0]->passed)->toBeFalse();
});
test('output contains passes when substring found', function (): void {
    $block  = ($this->makeBlock)('echo "Hello World";', assertions: [new OutputContainsAssertion('World', 1)]);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
    expect($result->assertionDetails)->toHaveCount(1);
    expect($result->assertionDetails[0]->type)->toBe('output_contains');
});
test('output contains fails when substring not found', function (): void {
    $block  = ($this->makeBlock)('echo "Hello";', assertions: [new OutputContainsAssertion('World', 1)]);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeFalse();
    expect($result->error)->not->toBeNull();
    $this->assertStringContainsString('does not contain', $result->error);
});
test('output matches passes with valid regex', function (): void {
    $block  = ($this->makeBlock)('echo "abc123";', assertions: [new OutputMatchesAssertion('/^[a-z]+\d+$/', 1)]);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
    expect($result->assertionDetails)->toHaveCount(1);
    expect($result->assertionDetails[0]->type)->toBe('output_matches');
});
test('output matches fails when pattern does not match', function (): void {
    $block  = ($this->makeBlock)('echo "hello";', assertions: [new OutputMatchesAssertion('/^\d+$/', 1)]);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeFalse();
    expect($result->error)->not->toBeNull();
    $this->assertStringContainsString('does not match pattern', $result->error);
});
test('output matches fails with invalid regex', function (): void {
    $block  = ($this->makeBlock)('echo "test";', assertions: [new OutputMatchesAssertion('/[invalid/', 1)]);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeFalse();
    expect($result->error)->not->toBeNull();
    $this->assertStringContainsString('Invalid regex pattern', $result->error);
});
test('output json passes with matching structure', function (): void {
    $block  = ($this->makeBlock)('echo json_encode(["name" => "test", "value" => 42]);', assertions: [new OutputJsonAssertion('{"name":"test","value":42}', 1)]);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
    expect($result->assertionDetails)->toHaveCount(1);
    expect($result->assertionDetails[0]->type)->toBe('output_json');
});
test('output json passes with different key order', function (): void {
    $block  = ($this->makeBlock)('echo json_encode(["b" => 2, "a" => 1]);', assertions: [new OutputJsonAssertion('{"a":1,"b":2}', 1)]);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
});
test('output json fails with structural mismatch', function (): void {
    $block  = ($this->makeBlock)('echo json_encode(["name" => "wrong"]);', assertions: [new OutputJsonAssertion('{"name":"expected"}', 1)]);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeFalse();
});
test('throws fails with wrong exception class', function (): void {
    $block  = ($this->makeBlock)('throw new \InvalidArgumentException("test");', Attribute::Throws, 'RuntimeException');
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeFalse();
    expect($result->error)->not->toBeNull();
    $this->assertStringContainsString('InvalidArgumentException', $result->error);
});
test('throws passes with matching message', function (): void {
    $block  = ($this->makeBlock)('throw new \RuntimeException("specific error message");', Attribute::Throws, 'RuntimeException', 'specific error');
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
});
test('throws fails with mismatched message', function (): void {
    $block  = ($this->makeBlock)('throw new \RuntimeException("actual message");', Attribute::Throws, 'RuntimeException', 'expected message');
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeFalse();
    expect($result->error)->not->toBeNull();
    $this->assertStringContainsString('expected message', $result->error);
});
test('throws passes without class check', function (): void {
    $block  = ($this->makeBlock)('throw new \LogicException("any");', Attribute::Throws);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
});
test('parse error fails when code is valid php', function (): void {
    $block  = ($this->makeBlock)('$x = 1 + 2;', Attribute::ParseError);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeFalse();
    expect($result->error)->not->toBeNull();
    $this->assertStringContainsString('Expected parse error', $result->error);
});
test('no run fails with syntax error', function (): void {
    $block  = ($this->makeBlock)('$x = {invalid;', Attribute::NoRun);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeFalse();
    expect($result->error)->not->toBeNull();
    $this->assertStringContainsString('Syntax error', $result->error);
});
test('group blocks share state', function (): void {
    $blocks = [
        ($this->makeBlock)('$x = 42;', group: 'math'),
        ($this->makeBlock)('echo $x;', group: 'math', assertions: [new OutputAssertion('42', 1)]),
    ];

    $results = $this->executor->executeGroup($blocks);

    expect($results)->toHaveCount(2);
    expect($results[0]->passed)->toBeTrue();
    expect($results[1]->passed)->toBeTrue();
});
test('group block without assertions passes as smoke test', function (): void {
    $blocks = [
        ($this->makeBlock)('$x = 1;', group: 'smoke'),
        ($this->makeBlock)('$y = $x + 1;', group: 'smoke'),
    ];

    $results = $this->executor->executeGroup($blocks);

    expect($results)->toHaveCount(2);
    expect($results[0]->passed)->toBeTrue();
    expect($results[1]->passed)->toBeTrue();
});
test('group block failure reports correct block', function (): void {
    $blocks = [
        ($this->makeBlock)('$x = 1; // => 1', group: 'fail'),
        ($this->makeBlock)('$y = 2; // => 99', group: 'fail'),
    ];

    $results = $this->executor->executeGroup($blocks);

    expect($results)->toHaveCount(2);
    expect($results[0]->passed)->toBeTrue();
    expect($results[1]->passed)->toBeFalse();
});
test('process crash reports exit code', function (): void {
    $block  = ($this->makeBlock)('exit(42);');
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeFalse();
    expect($result->error)->not->toBeNull();
    $this->assertStringContainsString('exit code', $result->error);
});
test('execution result includes duration', function (): void {
    $block  = ($this->makeBlock)('echo "fast";', assertions: [new OutputAssertion('fast', 1)]);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
    expect($result->duration)->not->toBeNull();
    expect($result->duration)->toBeGreaterThan(0.0);
});
test('executor with bootstrap resolver resolves per-block profiles', function (): void {
    $tmpDir = sys_get_temp_dir().'/doctest-executor-test-'.bin2hex(random_bytes(8));
    mkdir($tmpDir, 0755, true);
    file_put_contents($tmpDir.'/math.php', "<?php\nfunction doctest_add(\$a, \$b) { return \$a + \$b; }");

    $resolver = new \TestFlowLabs\DocTest\Config\BootstrapResolver($tmpDir);
    $executor = new Executor(bootstrapResolver: $resolver);

    $block = new CodeBlock(
        file: 'test.md',
        startLine: 1,
        rawCode: 'echo doctest_add(2, 3);',
        executableCode: 'echo doctest_add(2, 3);',
        attributes: new Attributes(bootstraps: ['math']),
        assertions: [new OutputAssertion('5', 1)],
    );

    $result = $executor->execute($block);

    expect($result->passed)->toBeTrue();
    expect($result->actualOutput)->toBe('5');

    unlink($tmpDir.'/math.php');
    rmdir($tmpDir);
});
test('executor without bootstrap resolver ignores block bootstraps', function (): void {
    $block  = ($this->makeBlock)('echo "ok";', assertions: [new OutputAssertion('ok', 1)]);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
});
test('executor resolves different bootstraps per block in executeAll', function (): void {
    $tmpDir = sys_get_temp_dir().'/doctest-executor-test-'.bin2hex(random_bytes(8));
    mkdir($tmpDir, 0755, true);
    file_put_contents($tmpDir.'/greet.php', "<?php\nfunction doctest_greet() { return 'hi'; }");

    $resolver = new \TestFlowLabs\DocTest\Config\BootstrapResolver($tmpDir);
    $executor = new Executor(bootstrapResolver: $resolver);

    $block1 = new CodeBlock(
        file: 'test.md', startLine: 1,
        rawCode: 'echo doctest_greet();',
        executableCode: 'echo doctest_greet();',
        attributes: new Attributes(bootstraps: ['greet']),
        assertions: [new OutputAssertion('hi', 1)],
    );

    $block2 = new CodeBlock(
        file: 'test.md', startLine: 5,
        rawCode: 'echo "plain";',
        executableCode: 'echo "plain";',
        attributes: new Attributes(),
        assertions: [new OutputAssertion('plain', 1)],
    );

    $results = $executor->executeAll([$block1, $block2]);

    expect($results)->toHaveCount(2);
    expect($results[0]->passed)->toBeTrue();
    expect($results[1]->passed)->toBeTrue();

    unlink($tmpDir.'/greet.php');
    rmdir($tmpDir);
});
test('group blocks with mismatched bootstrap profiles throws exception', function (): void {
    $tmpDir = sys_get_temp_dir().'/doctest-executor-test-'.bin2hex(random_bytes(8));
    mkdir($tmpDir, 0755, true);
    file_put_contents($tmpDir.'/math.php', "<?php\nfunction doctest_add(\$a, \$b) { return \$a + \$b; }");
    file_put_contents($tmpDir.'/greet.php', "<?php\nfunction doctest_greet() { return 'hi'; }");

    $resolver = new \TestFlowLabs\DocTest\Config\BootstrapResolver($tmpDir);
    $executor = new Executor(bootstrapResolver: $resolver);

    $block1 = new CodeBlock(
        file: 'test.md', startLine: 1,
        rawCode: '$x = 1;',
        executableCode: '$x = 1;',
        attributes: new Attributes(group: 'calc', bootstraps: ['math']),
        assertions: [],
    );

    $block2 = new CodeBlock(
        file: 'test.md', startLine: 5,
        rawCode: 'echo $x;',
        executableCode: 'echo $x;',
        attributes: new Attributes(group: 'calc', bootstraps: ['greet']),
        assertions: [new OutputAssertion('1', 1)],
    );

    expect(fn () => $executor->executeAll([$block1, $block2]))
        ->toThrow(RuntimeException::class, 'All blocks in group "calc" must have identical bootstrap profiles');

    unlink($tmpDir.'/math.php');
    unlink($tmpDir.'/greet.php');
    rmdir($tmpDir);
});
test('dd marker collects debug output without affecting pass fail', function (): void {
    $block  = ($this->makeBlock)('$x = 42; // => dd()');
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
    expect($result->debugOutputs)->toHaveCount(1);
    expect($result->debugOutputs[0])->toBeInstanceOf(\TestFlowLabs\DocTest\Executor\DebugOutput::class);
    expect($result->debugOutputs[0]->expression)->toBe('$x = 42');
    expect($result->debugOutputs[0]->value)->toBe('42');
    expect($result->debugOutputs[0]->line)->toBe(1);
});
test('dd marker does not create assertion details', function (): void {
    $block  = ($this->makeBlock)('$x = 42; // => dd()');
    $result = $this->executor->execute($block);

    expect($result->assertionDetails)->toBeEmpty();
    expect($result->debugOutputs)->toHaveCount(1);
});
test('dd marker mixed with result comment both pass', function (): void {
    $block  = ($this->makeBlock)("\$x = 42; // => dd()\n\$y = 10; // => 10");
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
    expect($result->debugOutputs)->toHaveCount(1);
    expect($result->debugOutputs[0]->value)->toBe('42');
    expect($result->assertionDetails)->toHaveCount(1);
    expect($result->assertionDetails[0]->type)->toBe('result_comment');
});
test('multiple dd markers collect all debug outputs', function (): void {
    $block  = ($this->makeBlock)("\$x = 1; // => dd()\n\$y = 2; // => dd()");
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
    expect($result->debugOutputs)->toHaveCount(2);
    expect($result->debugOutputs[0]->value)->toBe('1');
    expect($result->debugOutputs[1]->value)->toBe('2');
});
test('dd marker in group blocks collects debug outputs', function (): void {
    $blocks = [
        ($this->makeBlock)('$x = 42; // => dd()', group: 'dbg'),
        ($this->makeBlock)('$y = $x + 1; // => 43', group: 'dbg'),
    ];

    $results = $this->executor->executeGroup($blocks);

    expect($results)->toHaveCount(2);
    expect($results[0]->debugOutputs)->toHaveCount(1);
    expect($results[0]->debugOutputs[0]->value)->toBe('42');
    expect($results[1]->passed)->toBeTrue();
});
test('dd marker with output assertion both work', function (): void {
    $block  = ($this->makeBlock)("\$x = 42; // => dd()\necho \$x;", assertions: [new OutputAssertion('42', 3)]);
    $result = $this->executor->execute($block);

    expect($result->passed)->toBeTrue();
    expect($result->debugOutputs)->toHaveCount(1);
    expect($result->debugOutputs[0]->value)->toBe('42');
});
