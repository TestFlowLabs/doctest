<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Executor;

use TestFlowLabs\DocTest\CodeBlock\CodeBlock;
use TestFlowLabs\DocTest\Assertion\AssertionParser;

final readonly class CodeGenerator
{
    public function __construct(
        private ?string $bootstrapCode = null,
    ) {}

    public function generate(CodeBlock $block, ?string $setup = null, ?string $teardown = null, ?string $blockBootstrapCode = null): string
    {
        $dir      = $this->ensureTempDir();
        $filePath = $dir.'/doctest_'.bin2hex(random_bytes(16)).'.php';

        if ($block->attributes->isParseError()) {
            $bootstrapLine      = $this->bootstrapCode !== null ? $this->bootstrapCode."\n" : '';
            $blockBootstrapLine = $blockBootstrapCode !== null ? $blockBootstrapCode."\n" : '';
            $content            = "<?php\n".$bootstrapLine.$blockBootstrapLine.$block->rawCode."\n";
            $this->writeFile($filePath, $content);

            return $filePath;
        }

        $content = $this->generateInstrumented($block, $setup, $teardown, $blockBootstrapCode);
        $this->writeFile($filePath, $content);

        return $filePath;
    }

    /**
     * @param  array<CodeBlock>  $blocks
     */
    public function generateGroup(array $blocks, ?string $setup = null, ?string $teardown = null, ?string $blockBootstrapCode = null): string
    {
        $dir      = $this->ensureTempDir();
        $filePath = $dir.'/doctest_group_'.bin2hex(random_bytes(16)).'.php';
        $parser   = new AssertionParser();

        $lines = ["<?php\n"];

        if ($this->bootstrapCode !== null) {
            $lines[] = $this->bootstrapCode;
            $lines[] = '';
        }

        if ($blockBootstrapCode !== null) {
            $lines[] = $blockBootstrapCode;
            $lines[] = '';
        }

        $lines[] = '$__doctest_results = [];';
        $lines[] = '';

        if ($setup !== null) {
            $lines[] = $setup;
            $lines[] = '';
        }

        foreach ($blocks as $block) {
            $parsed = $parser->parse($block->rawCode);

            if ($block->assertions !== []) {
                $lines[] = 'ob_start();';
                $lines[] = $parsed->executableCode;
                $lines[] = '$__doctest_output = ob_get_clean();';

                foreach ($block->assertions as $assertion) {
                    $expected = $this->getExpectedValue($assertion);

                    if ($assertion instanceof \TestFlowLabs\DocTest\Assertion\ExpectAssertion) {
                        $lines[] = '$__doctest_results[] = [';
                        $lines[] = "    'type' => 'expect',";
                        $lines[] = "    'expression' => ".var_export($assertion->expression, true).',';
                        $lines[] = "    'passed' => (bool)(".$assertion->expression.'),';
                        $lines[] = "    'line' => ".$assertion->line().',';
                        $lines[] = '];';
                    } else {
                        $lines[] = '$__doctest_results[] = [';
                        $lines[] = "    'type' => ".var_export($assertion->type(), true).',';
                        $lines[] = "    'expected' => ".var_export($expected, true).',';
                        $lines[] = "    'actual' => \$__doctest_output,";
                        $lines[] = "    'line' => ".$assertion->line().',';
                        $lines[] = '];';
                    }

                    $lines[] = '';
                }
            } else {
                $lines[] = $parsed->executableCode;
                $lines[] = '';
            }

            foreach ($parsed->resultComments as $rc) {
                $lines[] = '$__doctest_result = '.$rc->expression.';';
                $lines[] = '$__doctest_results[] = [';
                $lines[] = "    'type' => 'result_comment',";
                $lines[] = "    'expected' => ".var_export($rc->expectedValue, true).',';
                $lines[] = "    'actual' => var_export(\$__doctest_result, true),";
                $lines[] = "    'expression' => ".var_export($rc->expression, true).',';
                $lines[] = "    'line' => ".$rc->line().',';
                $lines[] = '];';
                $lines[] = '';
            }

            foreach ($parsed->debugMarkers as $dm) {
                $lines[] = '$__doctest_result = '.$dm->expression.';';
                $lines[] = '$__doctest_results[] = [';
                $lines[] = "    'type' => 'debug',";
                $lines[] = "    'expression' => ".var_export($dm->expression, true).',';
                $lines[] = "    'value' => var_export(\$__doctest_result, true),";
                $lines[] = "    'line' => ".$dm->line().',';
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

    private function generateInstrumented(CodeBlock $block, ?string $setup = null, ?string $teardown = null, ?string $blockBootstrapCode = null): string
    {
        $parser = new AssertionParser();
        $parsed = $parser->parse($block->rawCode);

        $lines = ["<?php\n"];

        if ($this->bootstrapCode !== null) {
            $lines[] = $this->bootstrapCode;
            $lines[] = '';
        }

        if ($blockBootstrapCode !== null) {
            $lines[] = $blockBootstrapCode;
            $lines[] = '';
        }

        if ($block->attributes->isThrows()) {
            $lines[] = $this->generateThrowsWrapper($block, $parsed->executableCode);
        } else {
            $lines[] = $this->generateSegmentCapture($parsed, $block, $setup, $teardown);
        }

        return implode("\n", $lines);
    }

    private function generateSegmentCapture(\TestFlowLabs\DocTest\Assertion\AssertionParserResult $parsed, CodeBlock $block, ?string $setup = null, ?string $teardown = null): string
    {
        $lines   = [];
        $lines[] = '$__doctest_results = [];';
        $lines[] = '';

        if ($setup !== null) {
            $lines[] = $setup;
            $lines[] = '';
        }

        if ($block->assertions !== []) {
            $lines[] = 'ob_start();';
            $lines[] = $parsed->executableCode;
            $lines[] = '$__doctest_output = ob_get_clean();';

            foreach ($block->assertions as $assertion) {
                $expected = $this->getExpectedValue($assertion);

                if ($assertion instanceof \TestFlowLabs\DocTest\Assertion\ExpectAssertion) {
                    $lines[] = '$__doctest_results[] = [';
                    $lines[] = "    'type' => 'expect',";
                    $lines[] = "    'expression' => ".var_export($assertion->expression, true).',';
                    $lines[] = "    'passed' => (bool)(".$assertion->expression.'),';
                    $lines[] = "    'line' => ".$assertion->line().',';
                    $lines[] = '];';
                } else {
                    $lines[] = '$__doctest_results[] = [';
                    $lines[] = "    'type' => ".var_export($assertion->type(), true).',';
                    $lines[] = "    'expected' => ".var_export($expected, true).',';
                    $lines[] = "    'actual' => \$__doctest_output,";
                    $lines[] = "    'line' => ".$assertion->line().',';
                    $lines[] = '];';
                }

                $lines[] = '';
            }
        } else {
            $lines[] = $parsed->executableCode;
            $lines[] = '';
        }

        foreach ($parsed->resultComments as $rc) {
            $lines[] = '$__doctest_result = '.$rc->expression.';';
            $lines[] = '$__doctest_results[] = [';
            $lines[] = "    'type' => 'result_comment',";
            $lines[] = "    'expected' => ".var_export($rc->expectedValue, true).',';
            $lines[] = "    'actual' => var_export(\$__doctest_result, true),";
            $lines[] = "    'expression' => ".var_export($rc->expression, true).',';
            $lines[] = "    'line' => ".$rc->line().',';
            $lines[] = '];';
            $lines[] = '';
        }

        foreach ($parsed->debugMarkers as $dm) {
            $lines[] = '$__doctest_result = '.$dm->expression.';';
            $lines[] = '$__doctest_results[] = [';
            $lines[] = "    'type' => 'debug',";
            $lines[] = "    'expression' => ".var_export($dm->expression, true).',';
            $lines[] = "    'value' => var_export(\$__doctest_result, true),";
            $lines[] = "    'line' => ".$dm->line().',';
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
            $assertion instanceof \TestFlowLabs\DocTest\Assertion\OutputAssertion         => $assertion->expected,
            $assertion instanceof \TestFlowLabs\DocTest\Assertion\OutputContainsAssertion => $assertion->expected,
            $assertion instanceof \TestFlowLabs\DocTest\Assertion\OutputMatchesAssertion  => $assertion->pattern,
            $assertion instanceof \TestFlowLabs\DocTest\Assertion\OutputJsonAssertion     => $assertion->expectedJson,
            default                                                                       => '',
        };
    }

    private function ensureTempDir(): string
    {
        $dir = sys_get_temp_dir().'/doctest';

        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }

        return $dir;
    }

    private function writeFile(string $filePath, string $content): void
    {
        if (file_put_contents($filePath, $content) === false) {
            throw new \RuntimeException("Failed to write generated code to {$filePath}");
        }
    }

    private function generateThrowsWrapper(CodeBlock $block, string $executableCode): string
    {
        $lines   = [];
        $lines[] = 'try {';
        $lines[] = '    '.str_replace("\n", "\n    ", $executableCode);
        $lines[] = "    fwrite(STDERR, json_encode(['thrown' => false]));";
        $lines[] = '} catch (\Throwable $__doctest_e) {';
        $lines[] = '    fwrite(STDERR, json_encode([';
        $lines[] = "        'thrown' => true,";
        $lines[] = "        'class' => get_class(\$__doctest_e),";
        $lines[] = "        'message' => \$__doctest_e->getMessage(),";
        $lines[] = '    ]));';
        $lines[] = '}';

        return implode("\n", $lines);
    }
}
