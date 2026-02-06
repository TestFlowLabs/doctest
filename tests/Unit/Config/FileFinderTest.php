<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Config;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Config\FileFinder;

final class FileFinderTest extends TestCase
{
    private string $fixturesDir;

    private FileFinder $finder;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/../../Fixtures';
        $this->finder = new FileFinder();
    }

    #[Test]
    public function finds_markdown_files_in_directory(): void
    {
        $files = $this->finder->find([$this->fixturesDir], []);

        $this->assertNotEmpty($files);
        foreach ($files as $file) {
            $this->assertStringEndsWith('.md', $file);
        }
    }

    #[Test]
    public function finds_single_file_when_path_is_a_file(): void
    {
        $file = $this->fixturesDir . '/basic.md';
        $files = $this->finder->find([$file], []);

        $this->assertCount(1, $files);
        $this->assertStringEndsWith('basic.md', $files[0]);
    }

    #[Test]
    public function excludes_patterns(): void
    {
        $files = $this->finder->find([$this->fixturesDir], ['**/empty.md']);

        foreach ($files as $file) {
            $this->assertStringNotContainsString('empty.md', $file);
        }
    }

    #[Test]
    public function returns_empty_array_for_nonexistent_path(): void
    {
        $files = $this->finder->find(['/nonexistent/path'], []);

        $this->assertSame([], $files);
    }

    #[Test]
    public function returns_sorted_unique_list(): void
    {
        $file = $this->fixturesDir . '/basic.md';
        $files = $this->finder->find([$file, $file, $this->fixturesDir], []);

        $sorted = $files;
        sort($sorted);
        $this->assertSame($sorted, $files);
        $this->assertSame(array_unique($files), $files);
    }

    #[Test]
    public function handles_mix_of_files_and_directories(): void
    {
        $file = $this->fixturesDir . '/basic.md';
        $files = $this->finder->find([$file, $this->fixturesDir], []);

        $this->assertNotEmpty($files);
        $this->assertContains(realpath($file), array_map(realpath(...), $files));
    }
}
