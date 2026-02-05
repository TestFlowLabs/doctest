<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Executor;

use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;

final readonly class CodeGenerator
{
    public function generate(CodeBlock $block): string
    {
        $dir = sys_get_temp_dir() . '/doctest';

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filePath = $dir . '/doctest_' . uniqid() . '.php';

        if ($block->attributes->isParseError()) {
            $content = "<?php\n" . $block->rawCode . "\n";
            file_put_contents($filePath, $content);

            return $filePath;
        }

        $content = $this->generateInstrumented($block);
        file_put_contents($filePath, $content);

        return $filePath;
    }

    private function generateInstrumented(CodeBlock $block): string
    {
        $parser = new AssertionParser();
        $parsed = $parser->parse($block->rawCode);

        $lines = ["<?php\n"];

        if ($block->attributes->isThrows()) {
            $lines[] = $this->generateThrowsWrapper($block, $parsed->executableCode);
        } else {
            $lines[] = $this->generateSegmentCapture($parsed);
        }

        return implode("\n", $lines);
    }

    private function generateSegmentCapture(\TestFlowLabs\DocTest\Assertion\AssertionParserResult $parsed): string
    {
        $lines = [];
        $lines[] = '$__doctest_results = [];';
        $lines[] = '$__doctest_segment = 0;';
        $lines[] = '';

        foreach ($parsed->segments as $segment) {
            if ($segment->outputAssertion !== null) {
                $lines[] = 'ob_start();';
                $lines[] = $segment->code;
                $lines[] = '$__doctest_output = ob_get_clean();';
                $lines[] = '$__doctest_results[] = [';
                $lines[] = "    'type' => 'output',";
                $lines[] = "    'expected' => " . var_export($segment->outputAssertion->expected, true) . ',';
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

        $lines[] = 'fwrite(STDERR, json_encode($__doctest_results));';

        return implode("\n", $lines);
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
