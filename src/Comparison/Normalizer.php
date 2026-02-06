<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Comparison;

final readonly class Normalizer
{
    public function normalize(string $output): string
    {
        if ($output === '') {
            return '';
        }

        // CRLF → LF
        $output = str_replace("\r\n", "\n", $output);

        // Strip trailing whitespace per line
        $lines = explode("\n", $output);
        $lines = array_map(rtrim(...), $lines);

        // Remove leading blank lines
        while ($lines !== [] && $lines[0] === '') {
            array_shift($lines);
        }

        // Remove trailing blank lines
        while ($lines !== [] && $lines[array_key_last($lines)] === '') {
            array_pop($lines);
        }

        if ($lines === []) {
            return '';
        }

        // Single trailing newline
        return implode("\n", $lines)."\n";
    }
}
