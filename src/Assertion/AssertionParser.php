<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Assertion;

final readonly class AssertionParser
{
    public function parse(string $code): AssertionParserResult
    {
        $lines = explode("\n", $code);
        $resultComments = [];
        $executableLines = [];

        foreach ($lines as $lineIndex => $line) {
            $lineNumber = $lineIndex + 1;
            $trimmed = ltrim($line);

            // Check for result comment assertion: expression // => value
            if (preg_match('/^(.+?)\s*\/\/\s*=>\s*(.+)$/', $trimmed, $match) === 1) {
                $expression = rtrim(trim($match[1]), ';');
                $resultComments[] = new ResultCommentAssertion($expression, trim($match[2]), $lineNumber);
                // Keep the expression (without // => comment) in executable code
                $codeLine = rtrim($match[1]);
                if (! str_ends_with($codeLine, ';')) {
                    $codeLine .= ';';
                }
                $executableLines[] = $codeLine;
                continue;
            }

            // Regular code line
            $executableLines[] = $line;
        }

        return new AssertionParserResult(
            executableCode: implode("\n", $executableLines),
            resultComments: $resultComments,
        );
    }
}
