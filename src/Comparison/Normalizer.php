<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Comparison;

final readonly class Normalizer
{
    public function __construct(
        private bool $normalizeWhitespace = true,
        private bool $trimTrailing = true,
    ) {}

    public function normalize(string $output): string
    {
        if ($output === '') {
            return '';
        }

        // CRLF → LF (always applied)
        $output = str_replace("\r\n", "\n", $output);

        if ($this->trimTrailing) {
            // Strip trailing whitespace per line
            $lines  = explode("\n", $output);
            $lines  = array_map(rtrim(...), $lines);
            $output = implode("\n", $lines);
        }

        if ($this->normalizeWhitespace) {
            $lines = explode("\n", $output);

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

        // When normalizeWhitespace is off, still ensure we don't return empty for non-empty input
        if (trim($output) === '') {
            return '';
        }

        return $output;
    }
}
