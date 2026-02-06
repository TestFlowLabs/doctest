<?php

declare(strict_types=1);
beforeEach(function (): void {
    $this->binPath = __DIR__.'/../../bin/doctest';
});
test('bin doctest file exists', function (): void {
    expect($this->binPath)->toBeFile();
});
test('bin doctest is executable', function (): void {
    expect(is_executable($this->binPath))->toBeTrue();
});
test('bin doctest exits with zero', function (): void {
    $fixture = __DIR__.'/../Fixtures/basic.md';
    exec(PHP_BINARY.' '.escapeshellarg($this->binPath).' '.escapeshellarg($fixture).' 2>&1', $output, $exitCode);
    expect($exitCode)->toBe(0);
});
