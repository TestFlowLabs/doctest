<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Executor;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
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

final class ExecutorTest extends TestCase
{
    private Executor $executor;
    private AssertionParser $assertionParser;

    protected function setUp(): void
    {
        $this->executor        = new Executor();
        $this->assertionParser = new AssertionParser();
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
    public function executes_simple_echo_and_captures_output(): void
    {
        $block = $this->makeBlock(
            'echo "Hello World";',
            assertions: [new OutputAssertion('Hello World', 1)],
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
        $this->assertSame('Hello World', $result->actualOutput);
    }

    #[Test]
    public function executes_code_with_no_assertions_as_smoke_test(): void
    {
        $block  = $this->makeBlock('$x = 42;');
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function returns_skipped_result_for_ignore_attribute(): void
    {
        $block  = $this->makeBlock('echo "ignored";', Attribute::Ignore);
        $result = $this->executor->execute($block);

        $this->assertTrue($result->skipped);
        $this->assertTrue($result->passed);
    }

    #[Test]
    public function syntax_checks_no_run_blocks(): void
    {
        $block  = $this->makeBlock('$x = 1 + 2;', Attribute::NoRun);
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function detects_parse_errors_for_parse_error_blocks(): void
    {
        $block  = $this->makeBlock('$x = {invalid syntax;', Attribute::ParseError);
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function captures_exceptions_via_throws_attribute(): void
    {
        $block = $this->makeBlock(
            'throw new \RuntimeException("test error");',
            Attribute::Throws,
            'RuntimeException',
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function throws_attribute_fails_when_no_exception_thrown(): void
    {
        $block = $this->makeBlock(
            '$x = 1;',
            Attribute::Throws,
            'RuntimeException',
        );
        $result = $this->executor->execute($block);

        $this->assertFalse($result->passed);
    }

    #[Test]
    public function evaluates_expect_expressions(): void
    {
        $block = $this->makeBlock(
            '$x = 42;',
            assertions: [new ExpectAssertion('$x === 42', 2)],
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function expect_with_falsy_result_fails(): void
    {
        $block = $this->makeBlock(
            '$x = 42;',
            assertions: [new ExpectAssertion('$x === 99', 2)],
        );
        $result = $this->executor->execute($block);

        $this->assertFalse($result->passed);
    }

    #[Test]
    public function output_assertion_with_combined_output(): void
    {
        $block = $this->makeBlock(
            "echo \"a\";\necho \"b\";",
            assertions: [new OutputAssertion('ab', 1)],
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function handles_process_timeout_gracefully(): void
    {
        $executor = new Executor(timeout: 1);
        $block    = $this->makeBlock('sleep(10); echo "done";');
        $result   = $executor->execute($block);

        $this->assertFalse($result->passed);
        $this->assertNotNull($result->error);
    }

    #[Test]
    public function output_assertion_failure_reports_diff(): void
    {
        $block = $this->makeBlock(
            'echo "wrong";',
            assertions: [new OutputAssertion('right', 2)],
        );
        $result = $this->executor->execute($block);

        $this->assertFalse($result->passed);
        $this->assertSame('wrong', $result->actualOutput);
        $this->assertSame('right', $result->expectedOutput);
    }

    #[Test]
    public function evaluates_result_comment_with_matching_value(): void
    {
        $block  = $this->makeBlock('$x = 42; // => 42');
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function evaluates_result_comment_with_boolean(): void
    {
        $block  = $this->makeBlock('$x = true; // => true');
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function evaluates_result_comment_with_null(): void
    {
        $block  = $this->makeBlock('$x = null; // => NULL');
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function result_comment_fails_on_mismatch(): void
    {
        $block  = $this->makeBlock('$x = 42; // => 99');
        $result = $this->executor->execute($block);

        $this->assertFalse($result->passed);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('result_comment', $result->error);
    }

    #[Test]
    public function evaluates_multiple_result_comments(): void
    {
        $block  = $this->makeBlock("\$x = 1; // => 1\n\$y = 2; // => 2\n\$z = \$x + \$y; // => 3");
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function result_comment_mixed_with_html_output_assertion(): void
    {
        $block = $this->makeBlock(
            "\$x = 42; // => 42\necho \$x;",
            assertions: [new OutputAssertion('42', 3)],
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function populates_assertion_details_for_output(): void
    {
        $block = $this->makeBlock(
            'echo "Hello";',
            assertions: [new OutputAssertion('Hello', 2)],
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
        $this->assertCount(1, $result->assertionDetails);
        $this->assertSame('output', $result->assertionDetails[0]->type);
        $this->assertTrue($result->assertionDetails[0]->passed);
        $this->assertSame('Hello', $result->assertionDetails[0]->expected);
        $this->assertSame('Hello', $result->assertionDetails[0]->actual);
    }

    #[Test]
    public function populates_assertion_details_for_result_comment(): void
    {
        $block  = $this->makeBlock('$x = 42; // => 42');
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
        $this->assertCount(1, $result->assertionDetails);
        $this->assertSame('result_comment', $result->assertionDetails[0]->type);
        $this->assertTrue($result->assertionDetails[0]->passed);
        $this->assertSame('42', $result->assertionDetails[0]->expected);
        $this->assertSame('$x = 42', $result->assertionDetails[0]->expression);
    }

    #[Test]
    public function populates_assertion_details_for_expect(): void
    {
        $block = $this->makeBlock(
            '$x = 42;',
            assertions: [new ExpectAssertion('$x === 42', 2)],
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
        $this->assertCount(1, $result->assertionDetails);
        $this->assertSame('expect', $result->assertionDetails[0]->type);
        $this->assertTrue($result->assertionDetails[0]->passed);
    }

    #[Test]
    public function populates_assertion_details_on_failure(): void
    {
        $block  = $this->makeBlock("\$x = 1; // => 1\n\$y = 2; // => 99");
        $result = $this->executor->execute($block);

        $this->assertFalse($result->passed);
        $this->assertCount(2, $result->assertionDetails);
        $this->assertTrue($result->assertionDetails[0]->passed);
        $this->assertFalse($result->assertionDetails[1]->passed);
    }

    #[Test]
    public function populates_multiple_assertion_details(): void
    {
        $block = $this->makeBlock(
            "echo \"a\";\necho \"b\";",
            assertions: [
                new OutputAssertion('ab', 3),
            ],
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
        $this->assertCount(1, $result->assertionDetails);
        $this->assertSame('ab', $result->assertionDetails[0]->expected);
    }

    #[Test]
    public function execute_all_calls_on_result_callback_for_each_block(): void
    {
        $blocks = [
            $this->makeBlock('echo "a";', assertions: [new OutputAssertion('a', 1)]),
            $this->makeBlock('$x = 42;'),
            $this->makeBlock('echo "b";', assertions: [new OutputAssertion('b', 1)]),
        ];

        /** @var array<ExecutionResult> $callbackResults */
        $callbackResults = [];
        $results         = $this->executor->executeAll($blocks, onResult: function (ExecutionResult $result) use (&$callbackResults): void {
            $callbackResults[] = $result;
        });

        $this->assertCount(3, $results);
        $this->assertCount(3, $callbackResults);
        $this->assertSame($results, $callbackResults);
    }

    #[Test]
    public function execute_all_streams_results_in_execution_order(): void
    {
        $blocks = [
            $this->makeBlock('echo "first";', assertions: [new OutputAssertion('first', 1)]),
            $this->makeBlock('echo "second";', assertions: [new OutputAssertion('second', 1)]),
        ];

        /** @var array<string> $order */
        $order = [];
        $this->executor->executeAll($blocks, onResult: function (ExecutionResult $result) use (&$order): void {
            $order[] = $result->actualOutput ?? 'no-output';
        });

        $this->assertSame(['first', 'second'], $order);
    }

    #[Test]
    public function execute_all_without_callback_still_returns_results(): void
    {
        $blocks = [
            $this->makeBlock('echo "a";', assertions: [new OutputAssertion('a', 1)]),
            $this->makeBlock('$x = 1;'),
        ];

        $results = $this->executor->executeAll($blocks);

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]->passed);
        $this->assertTrue($results[1]->passed);
    }

    // --- Early termination ---

    #[Test]
    public function execute_all_stops_normal_blocks_when_callback_returns_false(): void
    {
        $blocks = [
            $this->makeBlock('echo "a";', assertions: [new OutputAssertion('wrong', 1)]),
            $this->makeBlock('echo "b";', assertions: [new OutputAssertion('b', 1)]),
            $this->makeBlock('echo "c";', assertions: [new OutputAssertion('c', 1)]),
        ];

        /** @var array<ExecutionResult> $collected */
        $collected = [];
        $results   = $this->executor->executeAll($blocks, onResult: function (ExecutionResult $result) use (&$collected): ?bool {
            $collected[] = $result;

            return $result->passed ? null : false;
        });

        $this->assertCount(1, $results);
        $this->assertCount(1, $collected);
        $this->assertFalse($results[0]->passed);
    }

    #[Test]
    public function execute_all_stops_grouped_blocks_when_callback_returns_false(): void
    {
        $blocks = [
            $this->makeBlock('echo "wrong";', group: 'grp', assertions: [new OutputAssertion('nope', 1)]),
            $this->makeBlock('echo "b";', group: 'grp', assertions: [new OutputAssertion('b', 1)]),
        ];

        /** @var array<ExecutionResult> $collected */
        $collected = [];
        $results   = $this->executor->executeAll($blocks, onResult: function (ExecutionResult $result) use (&$collected): ?bool {
            $collected[] = $result;

            return $result->passed ? null : false;
        });

        $this->assertCount(1, $collected);
        $this->assertFalse($collected[0]->passed);
    }

    #[Test]
    public function execute_all_skips_groups_when_stopped_during_normal_blocks(): void
    {
        $blocks = [
            $this->makeBlock('echo "wrong";', assertions: [new OutputAssertion('nope', 1)]),
            $this->makeBlock('echo "grouped";', group: 'grp', assertions: [new OutputAssertion('grouped', 1)]),
        ];

        /** @var array<ExecutionResult> $collected */
        $collected = [];
        $this->executor->executeAll($blocks, onResult: function (ExecutionResult $result) use (&$collected): ?bool {
            $collected[] = $result;

            return $result->passed ? null : false;
        });

        $this->assertCount(1, $collected);
        $this->assertFalse($collected[0]->passed);
    }

    // --- output_contains assertion ---

    #[Test]
    public function output_contains_passes_when_substring_found(): void
    {
        $block = $this->makeBlock(
            'echo "Hello World";',
            assertions: [new OutputContainsAssertion('World', 1)],
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
        $this->assertCount(1, $result->assertionDetails);
        $this->assertSame('output_contains', $result->assertionDetails[0]->type);
    }

    #[Test]
    public function output_contains_fails_when_substring_not_found(): void
    {
        $block = $this->makeBlock(
            'echo "Hello";',
            assertions: [new OutputContainsAssertion('World', 1)],
        );
        $result = $this->executor->execute($block);

        $this->assertFalse($result->passed);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('does not contain', $result->error);
    }

    // --- output_matches assertion ---

    #[Test]
    public function output_matches_passes_with_valid_regex(): void
    {
        $block = $this->makeBlock(
            'echo "abc123";',
            assertions: [new OutputMatchesAssertion('/^[a-z]+\d+$/', 1)],
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
        $this->assertCount(1, $result->assertionDetails);
        $this->assertSame('output_matches', $result->assertionDetails[0]->type);
    }

    #[Test]
    public function output_matches_fails_when_pattern_does_not_match(): void
    {
        $block = $this->makeBlock(
            'echo "hello";',
            assertions: [new OutputMatchesAssertion('/^\d+$/', 1)],
        );
        $result = $this->executor->execute($block);

        $this->assertFalse($result->passed);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('does not match pattern', $result->error);
    }

    #[Test]
    public function output_matches_fails_with_invalid_regex(): void
    {
        $block = $this->makeBlock(
            'echo "test";',
            assertions: [new OutputMatchesAssertion('/[invalid/', 1)],
        );
        $result = $this->executor->execute($block);

        $this->assertFalse($result->passed);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('Invalid regex pattern', $result->error);
    }

    // --- output_json assertion ---

    #[Test]
    public function output_json_passes_with_matching_structure(): void
    {
        $block = $this->makeBlock(
            'echo json_encode(["name" => "test", "value" => 42]);',
            assertions: [new OutputJsonAssertion('{"name":"test","value":42}', 1)],
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
        $this->assertCount(1, $result->assertionDetails);
        $this->assertSame('output_json', $result->assertionDetails[0]->type);
    }

    #[Test]
    public function output_json_passes_with_different_key_order(): void
    {
        $block = $this->makeBlock(
            'echo json_encode(["b" => 2, "a" => 1]);',
            assertions: [new OutputJsonAssertion('{"a":1,"b":2}', 1)],
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function output_json_fails_with_structural_mismatch(): void
    {
        $block = $this->makeBlock(
            'echo json_encode(["name" => "wrong"]);',
            assertions: [new OutputJsonAssertion('{"name":"expected"}', 1)],
        );
        $result = $this->executor->execute($block);

        $this->assertFalse($result->passed);
    }

    // --- throws edge cases ---

    #[Test]
    public function throws_fails_with_wrong_exception_class(): void
    {
        $block = $this->makeBlock(
            'throw new \InvalidArgumentException("test");',
            Attribute::Throws,
            'RuntimeException',
        );
        $result = $this->executor->execute($block);

        $this->assertFalse($result->passed);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('InvalidArgumentException', $result->error);
    }

    #[Test]
    public function throws_passes_with_matching_message(): void
    {
        $block = $this->makeBlock(
            'throw new \RuntimeException("specific error message");',
            Attribute::Throws,
            'RuntimeException',
            'specific error',
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function throws_fails_with_mismatched_message(): void
    {
        $block = $this->makeBlock(
            'throw new \RuntimeException("actual message");',
            Attribute::Throws,
            'RuntimeException',
            'expected message',
        );
        $result = $this->executor->execute($block);

        $this->assertFalse($result->passed);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('expected message', $result->error);
    }

    #[Test]
    public function throws_passes_without_class_check(): void
    {
        $block = $this->makeBlock(
            'throw new \LogicException("any");',
            Attribute::Throws,
        );
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
    }

    // --- parse_error edge case ---

    #[Test]
    public function parse_error_fails_when_code_is_valid_php(): void
    {
        $block  = $this->makeBlock('$x = 1 + 2;', Attribute::ParseError);
        $result = $this->executor->execute($block);

        $this->assertFalse($result->passed);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('Expected parse error', $result->error);
    }

    // --- no_run syntax error detection ---

    #[Test]
    public function no_run_fails_with_syntax_error(): void
    {
        $block  = $this->makeBlock('$x = {invalid;', Attribute::NoRun);
        $result = $this->executor->execute($block);

        $this->assertFalse($result->passed);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('Syntax error', $result->error);
    }

    // --- Group execution ---

    #[Test]
    public function group_blocks_share_state(): void
    {
        $blocks = [
            $this->makeBlock('$x = 42;', group: 'math'),
            $this->makeBlock('echo $x;', group: 'math', assertions: [new OutputAssertion('42', 1)]),
        ];

        $results = $this->executor->executeGroup($blocks);

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]->passed);
        $this->assertTrue($results[1]->passed);
    }

    #[Test]
    public function group_block_without_assertions_passes_as_smoke_test(): void
    {
        $blocks = [
            $this->makeBlock('$x = 1;', group: 'smoke'),
            $this->makeBlock('$y = $x + 1;', group: 'smoke'),
        ];

        $results = $this->executor->executeGroup($blocks);

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]->passed);
        $this->assertTrue($results[1]->passed);
    }

    #[Test]
    public function group_block_failure_reports_correct_block(): void
    {
        $blocks = [
            $this->makeBlock('$x = 1; // => 1', group: 'fail'),
            $this->makeBlock('$y = 2; // => 99', group: 'fail'),
        ];

        $results = $this->executor->executeGroup($blocks);

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]->passed);
        $this->assertFalse($results[1]->passed);
    }

    // --- Process crash ---

    #[Test]
    public function process_crash_reports_exit_code(): void
    {
        $block  = $this->makeBlock('exit(42);');
        $result = $this->executor->execute($block);

        $this->assertFalse($result->passed);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('exit code', $result->error);
    }

    // --- Duration tracking ---

    #[Test]
    public function execution_result_includes_duration(): void
    {
        $block  = $this->makeBlock('echo "fast";', assertions: [new OutputAssertion('fast', 1)]);
        $result = $this->executor->execute($block);

        $this->assertTrue($result->passed);
        $this->assertNotNull($result->duration);
        $this->assertGreaterThan(0.0, $result->duration);
    }
}
