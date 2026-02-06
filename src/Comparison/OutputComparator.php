<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Comparison;

final readonly class OutputComparator
{
    private Normalizer $normalizer;
    private WildcardMatcher $wildcardMatcher;

    public function __construct()
    {
        $this->normalizer      = new Normalizer();
        $this->wildcardMatcher = new WildcardMatcher();
    }

    public function compare(string $expected, string $actual): ComparisonResult
    {
        $normalizedExpected = $this->normalizer->normalize($expected);
        $normalizedActual   = $this->normalizer->normalize($actual);

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

    public function compareJson(string $expectedJson, string $actualJson): ComparisonResult
    {
        $expectedDecoded = json_decode($expectedJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new ComparisonResult(
                passed: false,
                normalizedExpected: 'Expected JSON is invalid: '.json_last_error_msg(),
                normalizedActual: $actualJson,
            );
        }

        $actualDecoded = json_decode($actualJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new ComparisonResult(
                passed: false,
                normalizedExpected: $expectedJson,
                normalizedActual: 'Actual JSON output is invalid: '.json_last_error_msg(),
            );
        }

        $this->recursiveKeySort($expectedDecoded);
        $this->recursiveKeySort($actualDecoded);

        return new ComparisonResult(
            passed: $expectedDecoded === $actualDecoded,
            normalizedExpected: $expectedJson,
            normalizedActual: $actualJson,
        );
    }

    private function recursiveKeySort(mixed &$value): void
    {
        if (!is_array($value)) {
            return;
        }

        foreach ($value as &$item) {
            $this->recursiveKeySort($item);
        }

        if ($this->isAssociativeArray($value)) {
            ksort($value);
        }
    }

    /**
     * @param  array<mixed>  $array
     */
    private function isAssociativeArray(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
