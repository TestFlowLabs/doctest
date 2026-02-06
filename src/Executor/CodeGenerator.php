<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Executor;

use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;

final readonly class CodeGenerator
{
    public function generate(CodeBlock $block, ?string $setup = null, ?string $teardown = null): string
    {
        $dir = sys_get_temp_dir() . '/doctest';

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filePath = $dir . '/doctest_' . bin2hex(random_bytes(16)) . '.php';

        if ($block->attributes->isParseError()) {
            $content = "<?php\n" . $block->rawCode . "\n";
            $this->writeFile($filePath, $content);

            return $filePath;
        }

        $content = $this->generateInstrumented($block, $setup, $teardown);
        $this->writeFile($filePath, $content);

        return $filePath;
    }

    /**
     * @param array<CodeBlock> $blocks
     */
    public function generateGroup(array $blocks, ?string $setup = null, ?string $teardown = null): string
    {
        $dir = sys_get_temp_dir() . '/doctest';

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filePath = $dir . '/doctest_group_' . bin2hex(random_bytes(16)) . '.php';
        $parser = new AssertionParser();

        $lines = ["<?php\n"];
        $lines[] = '$__doctest_results = [];';
        $lines[] = '$__doctest_segment = 0;';
        $lines[] = '';

        if ($setup !== null) {
            $lines[] = $setup;
            $lines[] = '';
        }

        foreach ($blocks as $block) {
            $parsed = $parser->parse($block->rawCode);

            foreach ($parsed->segments as $segment) {
                if ($segment->outputAssertion !== null) {
                    $lines[] = 'ob_start();';
                    $lines[] = $segment->code;
                    $lines[] = '$__doctest_output = ob_get_clean();';
                    $expected = $this->getExpectedValue($segment->outputAssertion);
                    $lines[] = '$__doctest_results[] = [';
                    $lines[] = "    'type' => " . var_export($segment->outputAssertion->type(), true) . ',';
                    $lines[] = "    'expected' => " . var_export($expected, true) . ',';
                    $lines[] = "    'actual' => \$__doctest_output,";
                    $lines[] = "    'line' => " . $segment->outputAssertion->line() . ',';
                    $lines[] = '];';
                    $lines[] = '$__doctest_segment++;';
                    $lines[] = '';
                } else {
                    $lines[] = $segment->code;
                    $lines[] = '';
                }
            }

            foreach ($parsed->expects as $expect) {
                $lines[] = '$__doctest_results[] = [';
                $lines[] = "    'type' => 'expect',";
                $lines[] = "    'expression' => " . var_export($expect->expression, true) . ',';
                $lines[] = "    'passed' => (bool)(" . $expect->expression . '),';
                $lines[] = "    'line' => " . $expect->line() . ',';
                $lines[] = '];';
                $lines[] = '';
            }
        }

        if ($teardown !== null) {
            $lines[] = $teardown;
            $lines[] = '';
        }

        $lines[] = 'fwrite(STDERR, json_encode($__doctest_results));';

        $this->writeFile($filePath, implode("\n", $lines));

        return $filePath;
    }

    private function generateInstrumented(CodeBlock $block, ?string $setup = null, ?string $teardown = null): string
    {
        $parser = new AssertionParser();
        $parsed = $parser->parse($block->rawCode);

        $lines = ["<?php\n"];

        if ($block->attributes->isThrows()) {
            $lines[] = $this->generateThrowsWrapper($block, $parsed->executableCode);
        } else {
            $lines[] = $this->generateSegmentCapture($parsed, $setup, $teardown);
        }

        return implode("\n", $lines);
    }

    private function generateSegmentCapture(\TestFlowLabs\DocTest\Assertion\AssertionParserResult $parsed, ?string $setup = null, ?string $teardown = null): string
    {
        $lines = [];
        $lines[] = '$__doctest_results = [];';
        $lines[] = '$__doctest_segment = 0;';
        $lines[] = '';

        if ($setup !== null) {
            $lines[] = $setup;
            $lines[] = '';
        }

        foreach ($parsed->segments as $segment) {
            if ($segment->outputAssertion !== null) {
                $lines[] = 'ob_start();';
                $lines[] = $segment->code;
                $lines[] = '$__doctest_output = ob_get_clean();';
                $expected = $this->getExpectedValue($segment->outputAssertion);
                $lines[] = '$__doctest_results[] = [';
                $lines[] = "    'type' => " . var_export($segment->outputAssertion->type(), true) . ',';
                $lines[] = "    'expected' => " . var_export($expected, true) . ',';
                $lines[] = "    'actual' => \$__doctest_output,";
                $lines[] = "    'line' => " . $segment->outputAssertion->line() . ',';
                $lines[] = '];';
                $lines[] = '$__doctest_segment++;';
                $lines[] = '';
            } else {
                $lines[] = $segment->code;
                $lines[] = '';
            }
        }

        foreach ($parsed->expects as $expect) {
            $lines[] = '$__doctest_results[] = [';
            $lines[] = "    'type' => 'expect',";
            $lines[] = "    'expression' => " . var_export($expect->expression, true) . ',';
            $lines[] = "    'passed' => (bool)(" . $expect->expression . '),';
            $lines[] = "    'line' => " . $expect->line() . ',';
            $lines[] = '];';
            $lines[] = '';
        }

        if ($teardown !== null) {
            $lines[] = $teardown;
            $lines[] = '';
        }

        $lines[] = 'fwrite(STDERR, json_encode($__doctest_results));';

        return implode("\n", $lines);
    }

    private function getExpectedValue(\TestFlowLabs\DocTest\Assertion\Assertion $assertion): string
    {
        return match (true) {
            $assertion instanceof \TestFlowLabs\DocTest\Assertion\OutputAssertion => $assertion->expected,
            $assertion instanceof \TestFlowLabs\DocTest\Assertion\OutputContainsAssertion => $assertion->expected,
            $assertion instanceof \TestFlowLabs\DocTest\Assertion\OutputMatchesAssertion => $assertion->pattern,
            $assertion instanceof \TestFlowLabs\DocTest\Assertion\OutputJsonAssertion => $assertion->expectedJson,
            default => '',
        };
    }

    private function writeFile(string $filePath, string $content): void
    {
        if (file_put_contents($filePath, $content) === false) {
            throw new \RuntimeException("Failed to write generated code to {$filePath}");
        }
    }

    private function generateThrowsWrapper(CodeBlock $block, string $executableCode): string
    {
        $lines = [];
        $lines[] = 'try {';
        $lines[] = '    ' . str_replace("\n", "\n    ", $executableCode);
        $lines[] = "    fwrite(STDERR, json_encode(['thrown' => false]));";
        $lines[] = '} catch (\Throwable $__doctest_e) {';
        $lines[] = "    fwrite(STDERR, json_encode([";
        $lines[] = "        'thrown' => true,";
        $lines[] = "        'class' => get_class(\$__doctest_e),";
        $lines[] = "        'message' => \$__doctest_e->getMessage(),";
        $lines[] = '    ]));';
        $lines[] = '}';

        return implode("\n", $lines);
    }
}
