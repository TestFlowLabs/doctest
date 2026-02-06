<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Comparison;

final readonly class OutputComparator
{
    private Normalizer $normalizer;

    private WildcardMatcher $wildcardMatcher;

    public function __construct()
    {
        $this->normalizer = new Normalizer();
        $this->wildcardMatcher = new WildcardMatcher();
    }

    public function compare(string $expected, string $actual): ComparisonResult
    {
        $normalizedExpected = $this->normalizer->normalize($expected);
        $normalizedActual = $this->normalizer->normalize($actual);

        if ($this->wildcardMatcher->hasWildcards($normalizedExpected)) {
            $passed = $this->wildcardMatcher->matches($normalizedActual, $normalizedExpected);
        } else {
            $passed = $normalizedExpected === $normalizedActual;
        }

        return new ComparisonResult(
            passed: $passed,
            normalizedExpected: $normalizedExpected,
            normalizedActual: $normalizedActual,
        );
    }
}
