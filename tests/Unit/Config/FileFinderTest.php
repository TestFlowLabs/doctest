<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\DocTest\Config\FileFinder;

final class FileFinderTest extends TestCase
{
    private string $fixturesDir;
    private FileFinder $finder;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__.'/../../Fixtures';
        $this->finder      = new FileFinder();
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
        $file  = $this->fixturesDir.'/basic.md';
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
        $file  = $this->fixturesDir.'/basic.md';
        $files = $this->finder->find([$file, $file, $this->fixturesDir], []);

        $sorted = $files;
        sort($sorted);
        $this->assertSame($sorted, $files);
        $this->assertSame(array_unique($files), $files);
    }

    #[Test]
    public function handles_mix_of_files_and_directories(): void
    {
        $file  = $this->fixturesDir.'/basic.md';
        $files = $this->finder->find([$file, $this->fixturesDir], []);

        $this->assertNotEmpty($files);
        $this->assertContains(realpath($file), array_map(realpath(...), $files));
    }

    #[Test]
    public function excludes_directory_path_without_glob_wildcards(): void
    {
        $baseDir = sys_get_temp_dir().'/doctest_finder_test_'.uniqid();
        mkdir($baseDir.'/subdir', 0o777, true);
        mkdir($baseDir.'/excluded/nested', 0o777, true);
        file_put_contents($baseDir.'/subdir/keep.md', '# Keep');
        file_put_contents($baseDir.'/excluded/nested/skip.md', '# Skip');

        $files = $this->finder->find([$baseDir], ['excluded']);

        $this->assertCount(1, $files);
        $this->assertStringContainsString('keep.md', $files[0]);

        // Cleanup
        unlink($baseDir.'/subdir/keep.md');
        unlink($baseDir.'/excluded/nested/skip.md');
        rmdir($baseDir.'/excluded/nested');
        rmdir($baseDir.'/excluded');
        rmdir($baseDir.'/subdir');
        rmdir($baseDir);
    }

    #[Test]
    public function excludes_nested_directory_path(): void
    {
        $baseDir = sys_get_temp_dir().'/doctest_finder_test_'.uniqid();
        mkdir($baseDir.'/docs/node_modules/pkg', 0o777, true);
        mkdir($baseDir.'/docs/guide', 0o777, true);
        file_put_contents($baseDir.'/docs/guide/intro.md', '# Intro');
        file_put_contents($baseDir.'/docs/node_modules/pkg/README.md', '# Pkg');

        $files = $this->finder->find([$baseDir], ['docs/node_modules']);

        $this->assertCount(1, $files);
        $this->assertStringContainsString('intro.md', $files[0]);

        // Cleanup
        unlink($baseDir.'/docs/guide/intro.md');
        unlink($baseDir.'/docs/node_modules/pkg/README.md');
        rmdir($baseDir.'/docs/node_modules/pkg');
        rmdir($baseDir.'/docs/node_modules');
        rmdir($baseDir.'/docs/guide');
        rmdir($baseDir.'/docs');
        rmdir($baseDir);
    }

    #[Test]
    public function excludes_directory_path_with_relative_input(): void
    {
        $baseDir = sys_get_temp_dir().'/doctest_finder_reltest_'.uniqid();
        mkdir($baseDir.'/docs/node_modules/pkg', 0o777, true);
        mkdir($baseDir.'/docs/guide', 0o777, true);
        file_put_contents($baseDir.'/docs/guide/intro.md', '# Intro');
        file_put_contents($baseDir.'/docs/node_modules/pkg/README.md', '# Pkg');

        $originalDir = getcwd();
        chdir($baseDir);

        try {
            $files = $this->finder->find(['docs'], ['docs/node_modules']);

            $this->assertCount(1, $files);
            $this->assertStringContainsString('intro.md', $files[0]);
            foreach ($files as $file) {
                $this->assertStringNotContainsString('node_modules', $file);
            }
        } finally {
            chdir($originalDir);

            // Cleanup
            unlink($baseDir.'/docs/guide/intro.md');
            unlink($baseDir.'/docs/node_modules/pkg/README.md');
            rmdir($baseDir.'/docs/node_modules/pkg');
            rmdir($baseDir.'/docs/node_modules');
            rmdir($baseDir.'/docs/guide');
            rmdir($baseDir.'/docs');
            rmdir($baseDir);
        }
    }

    #[Test]
    public function excludes_simple_directory_name_with_relative_input(): void
    {
        $baseDir = sys_get_temp_dir().'/doctest_finder_reltest2_'.uniqid();
        mkdir($baseDir.'/docs/node_modules/pkg', 0o777, true);
        mkdir($baseDir.'/docs/guide', 0o777, true);
        file_put_contents($baseDir.'/docs/guide/intro.md', '# Intro');
        file_put_contents($baseDir.'/docs/node_modules/pkg/README.md', '# Pkg');

        $originalDir = getcwd();
        chdir($baseDir);

        try {
            $files = $this->finder->find(['docs'], ['node_modules']);

            $this->assertCount(1, $files);
            $this->assertStringContainsString('intro.md', $files[0]);
            foreach ($files as $file) {
                $this->assertStringNotContainsString('node_modules', $file);
            }
        } finally {
            chdir($originalDir);

            // Cleanup
            unlink($baseDir.'/docs/guide/intro.md');
            unlink($baseDir.'/docs/node_modules/pkg/README.md');
            rmdir($baseDir.'/docs/node_modules/pkg');
            rmdir($baseDir.'/docs/node_modules');
            rmdir($baseDir.'/docs/guide');
            rmdir($baseDir.'/docs');
            rmdir($baseDir);
        }
    }

    // --- Edge cases ---

    #[Test]
    public function empty_paths_returns_empty(): void
    {
        $files = $this->finder->find([], []);

        $this->assertSame([], $files);
    }

    #[Test]
    public function directory_with_no_md_files_returns_empty(): void
    {
        $baseDir = sys_get_temp_dir().'/doctest_finder_nomd_'.uniqid();
        mkdir($baseDir, 0o777, true);
        file_put_contents($baseDir.'/readme.txt', 'not markdown');

        $files = $this->finder->find([$baseDir], []);

        $this->assertSame([], $files);

        unlink($baseDir.'/readme.txt');
        rmdir($baseDir);
    }

    #[Test]
    public function excludes_by_filename_pattern(): void
    {
        $baseDir = sys_get_temp_dir().'/doctest_finder_fnmatch_'.uniqid();
        mkdir($baseDir, 0o777, true);
        file_put_contents($baseDir.'/keep.md', '# Keep');
        file_put_contents($baseDir.'/draft-intro.md', '# Draft');

        $files = $this->finder->find([$baseDir], ['draft-*.md']);

        $this->assertCount(1, $files);
        $this->assertStringContainsString('keep.md', $files[0]);

        unlink($baseDir.'/keep.md');
        unlink($baseDir.'/draft-intro.md');
        rmdir($baseDir);
    }

    #[Test]
    public function multiple_exclude_patterns(): void
    {
        $baseDir = sys_get_temp_dir().'/doctest_finder_multi_'.uniqid();
        mkdir($baseDir, 0o777, true);
        file_put_contents($baseDir.'/keep.md', '# Keep');
        file_put_contents($baseDir.'/draft.md', '# Draft');
        file_put_contents($baseDir.'/temp.md', '# Temp');

        $files = $this->finder->find([$baseDir], ['draft.md', 'temp.md']);

        $this->assertCount(1, $files);
        $this->assertStringContainsString('keep.md', $files[0]);

        unlink($baseDir.'/keep.md');
        unlink($baseDir.'/draft.md');
        unlink($baseDir.'/temp.md');
        rmdir($baseDir);
    }
}
