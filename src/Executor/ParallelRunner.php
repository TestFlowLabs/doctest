<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Executor;

final readonly class ParallelRunner
{
    public function __construct(
        public int $workers = 1,
    ) {}

    /**
     * @param array<string> $files
     *
     * @return array<array<string>>
     */
    public function distribute(array $files): array
    {
        $batches = array_fill(0, $this->workers, []);

        foreach ($files as $index => $file) {
            $batches[$index % $this->workers][] = $file;
        }

        return $batches;
    }
}
