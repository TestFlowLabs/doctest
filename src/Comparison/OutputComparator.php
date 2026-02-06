<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Comparison;

final readonly class OutputComparator
{
    private Normalizer $normalizer;

    public function __construct()
    {
        $this->normalizer = new Normalizer();
    }

    public function compare(string $expected, string $actual): ComparisonResult
    {
        $normalizedExpected = $this->normalizer->normalize($expected);
        $normalizedActual = $this->normalizer->normalize($actual);

        return new ComparisonResult(
            passed: $normalizedExpected === $normalizedActual,
            normalizedExpected: $normalizedExpected,
            normalizedActual: $normalizedActual,
        );
    }
}
