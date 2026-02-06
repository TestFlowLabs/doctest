<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Reporter;

use TestFlowLabs\DocTest\Executor\ExecutionResult;

final class JUnitReporter
{
    /**
     * @param array<ExecutionResult> $results
     */
    public function generate(array $results): string
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $testsuites = $doc->createElement('testsuites');
        $doc->appendChild($testsuites);

        $totalTests = count($results);
        $totalFailures = 0;
        $totalSkipped = 0;
        $totalTime = 0.0;

        foreach ($results as $result) {
            if (! $result->passed && ! $result->skipped) {
                $totalFailures++;
            }
            if ($result->skipped) {
                $totalSkipped++;
            }
            $totalTime += $result->duration;
        }

        $testsuites->setAttribute('tests', (string) $totalTests);
        $testsuites->setAttribute('failures', (string) $totalFailures);
        $testsuites->setAttribute('time', (string) $totalTime);

        $grouped = $this->groupByFile($results);

        foreach ($grouped as $file => $fileResults) {
            $suite = $this->buildTestSuite($doc, $file, $fileResults);
            $testsuites->appendChild($suite);
        }

        return $doc->saveXML() ?: '';
    }

    /**
     * @param array<ExecutionResult> $results
     */
    public function generateToFile(array $results, string $filePath): void
    {
        $xml = $this->generate($results);
        file_put_contents($filePath, $xml);
    }

    /**
     * @param array<ExecutionResult> $results
     *
     * @return array<string, array<ExecutionResult>>
     */
    private function groupByFile(array $results): array
    {
        $grouped = [];

        foreach ($results as $result) {
            $grouped[$result->codeBlock->file][] = $result;
        }

        return $grouped;
    }

    /**
     * @param array<ExecutionResult> $results
     */
    private function buildTestSuite(\DOMDocument $doc, string $file, array $results): \DOMElement
    {
        $suite = $doc->createElement('testsuite');
        $suite->setAttribute('name', $file);
        $suite->setAttribute('tests', (string) count($results));

        $failures = 0;
        $skipped = 0;
        $time = 0.0;

        foreach ($results as $result) {
            if (! $result->passed && ! $result->skipped) {
                $failures++;
            }
            if ($result->skipped) {
                $skipped++;
            }
            $time += $result->duration;

            $testcase = $this->buildTestCase($doc, $result);
            $suite->appendChild($testcase);
        }

        $suite->setAttribute('failures', (string) $failures);
        $suite->setAttribute('skipped', (string) $skipped);
        $suite->setAttribute('time', (string) $time);

        return $suite;
    }

    private function buildTestCase(\DOMDocument $doc, ExecutionResult $result): \DOMElement
    {
        $testcase = $doc->createElement('testcase');
        $testcase->setAttribute('name', "Line {$result->codeBlock->startLine}");
        $testcase->setAttribute('classname', $result->codeBlock->file);
        $testcase->setAttribute('time', (string) $result->duration);

        if ($result->skipped) {
            $testcase->appendChild($doc->createElement('skipped'));
        } elseif (! $result->passed) {
            $failure = $doc->createElement('failure');
            $failure->setAttribute('type', 'AssertionError');
            $message = $result->error ?? 'Assertion failed';
            if ($result->diff !== null) {
                $message .= "\n" . $result->diff;
            }
            $failure->appendChild($doc->createTextNode($message));
            $testcase->appendChild($failure);
        }

        return $testcase;
    }
}
