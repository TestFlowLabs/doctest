<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Assertion;

final readonly class AssertionParser
{
    public function parse(string $code): AssertionParserResult
    {
        $lines = explode("\n", $code);
        $assertions = [];
        $expects = [];
        $executableLines = [];
        $segments = [];
        $currentSegmentCode = [];
        $inMultiLineOutput = false;
        $multiLineExpected = [];
        $multiLineStartLine = 0;

        foreach ($lines as $lineIndex => $line) {
            $lineNumber = $lineIndex + 1;
            $trimmed = ltrim($line);

            // Check if we're continuing a multi-line output
            if ($inMultiLineOutput) {
                if (str_starts_with($trimmed, '// ') && ! str_starts_with($trimmed, '// Output:') && ! str_starts_with($trimmed, '// OutputContains:') && ! str_starts_with($trimmed, '// OutputMatches:') && ! str_starts_with($trimmed, '// OutputJson:') && ! str_starts_with($trimmed, '// Expect:')) {
                    // Continuation line: strip "// " prefix
                    $multiLineExpected[] = substr($trimmed, 3);
                    continue;
                }

                // End of multi-line output
                $expected = implode("\n", $multiLineExpected);
                $assertion = new OutputAssertion($expected, $multiLineStartLine);
                $assertions[] = $assertion;
                $segments[] = new CodeSegment(implode("\n", $currentSegmentCode), $assertion);
                $currentSegmentCode = [];
                $inMultiLineOutput = false;
                $multiLineExpected = [];
            }

            // Check for single-line Output assertion
            if (preg_match('/^\/\/\s*Output:\s*(.+)$/', $trimmed, $match) === 1) {
                $assertion = new OutputAssertion(trim($match[1]), $lineNumber);
                $assertions[] = $assertion;
                $segments[] = new CodeSegment(implode("\n", $currentSegmentCode), $assertion);
                $currentSegmentCode = [];
                continue;
            }

            // Check for multi-line Output assertion start
            if (preg_match('/^\/\/\s*Output:\s*$/', $trimmed) === 1) {
                $inMultiLineOutput = true;
                $multiLineStartLine = $lineNumber;
                continue;
            }

            // Check for OutputContains assertion
            if (preg_match('/^\/\/\s*OutputContains:\s*(.+)$/', $trimmed, $match) === 1) {
                $assertion = new OutputContainsAssertion(trim($match[1]), $lineNumber);
                $assertions[] = $assertion;
                $segments[] = new CodeSegment(implode("\n", $currentSegmentCode), $assertion);
                $currentSegmentCode = [];
                continue;
            }

            // Check for OutputMatches assertion
            if (preg_match('/^\/\/\s*OutputMatches:\s*(.+)$/', $trimmed, $match) === 1) {
                $assertion = new OutputMatchesAssertion(trim($match[1]), $lineNumber);
                $assertions[] = $assertion;
                $segments[] = new CodeSegment(implode("\n", $currentSegmentCode), $assertion);
                $currentSegmentCode = [];
                continue;
            }

            // Check for OutputJson assertion
            if (preg_match('/^\/\/\s*OutputJson:\s*(.+)$/', $trimmed, $match) === 1) {
                $assertion = new OutputJsonAssertion(trim($match[1]), $lineNumber);
                $assertions[] = $assertion;
                $segments[] = new CodeSegment(implode("\n", $currentSegmentCode), $assertion);
                $currentSegmentCode = [];
                continue;
            }

            // Check for Expect assertion
            if (preg_match('/^\/\/\s*Expect:\s*(.+)$/', $trimmed, $match) === 1) {
                $expects[] = new ExpectAssertion(trim($match[1]), $lineNumber);
                continue;
            }

            // Regular code line
            $executableLines[] = $line;
            $currentSegmentCode[] = $line;
        }

        // Handle trailing multi-line output
        if ($inMultiLineOutput && $multiLineExpected !== []) {
            $expected = implode("\n", $multiLineExpected);
            $assertion = new OutputAssertion($expected, $multiLineStartLine);
            $assertions[] = $assertion;
            $segments[] = new CodeSegment(implode("\n", $currentSegmentCode), $assertion);
            $currentSegmentCode = [];
        }

        // Add remaining code as a segment without assertion
        if ($currentSegmentCode !== []) {
            $segments[] = new CodeSegment(implode("\n", $currentSegmentCode));
        }

        return new AssertionParserResult(
            assertions: $assertions,
            executableCode: implode("\n", $executableLines),
            segments: $segments,
            expects: $expects,
        );
    }
}
