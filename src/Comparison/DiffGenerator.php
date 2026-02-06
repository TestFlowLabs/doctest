<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Comparison;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

final readonly class DiffGenerator
{
    private Differ $differ;

    public function __construct()
    {
        $this->differ = new Differ(new UnifiedDiffOutputBuilder('', false));
    }

    public function generate(string $expected, string $actual): string
    {
        if ($expected === $actual) {
            return '';
        }

        $diff = $this->differ->diff($expected, $actual);

        // Filter to only show +/- lines (not context)
        $lines = explode("\n", $diff);
        $filtered = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, '-')) {
                $filtered[] = '- ' . substr($line, 1);
            } elseif (str_starts_with($line, '+')) {
                $filtered[] = '+ ' . substr($line, 1);
            }
        }

        return implode("\n", $filtered);
    }
}
