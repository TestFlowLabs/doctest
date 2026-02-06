<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Config\FileFinder;

beforeEach(function (): void {
    $this->fixturesDir = __DIR__.'/../../Fixtures';
    $this->finder      = new FileFinder();
});
test('finds markdown files in directory', function (): void {
    $files = $this->finder->find([$this->fixturesDir], []);

    expect($files)->not->toBeEmpty();
    foreach ($files as $file) {
        expect($file)->toEndWith('.md');
    }
});
test('finds single file when path is a file', function (): void {
    $file  = $this->fixturesDir.'/basic.md';
    $files = $this->finder->find([$file], []);

    expect($files)->toHaveCount(1);
    expect($files[0])->toEndWith('basic.md');
});
test('excludes patterns', function (): void {
    $files = $this->finder->find([$this->fixturesDir], ['**/empty.md']);

    foreach ($files as $file) {
        $this->assertStringNotContainsString('empty.md', $file);
    }
});
test('returns empty array for nonexistent path', function (): void {
    $files = $this->finder->find(['/nonexistent/path'], []);

    expect($files)->toBe([]);
});
test('returns sorted unique list', function (): void {
    $file  = $this->fixturesDir.'/basic.md';
    $files = $this->finder->find([$file, $file, $this->fixturesDir], []);

    $sorted = $files;
    sort($sorted);
    expect($files)->toBe($sorted);
    expect($files)->toBe(array_unique($files));
});
test('handles mix of files and directories', function (): void {
    $file  = $this->fixturesDir.'/basic.md';
    $files = $this->finder->find([$file, $this->fixturesDir], []);

    expect($files)->not->toBeEmpty();
    expect(array_map(realpath(...), $files))->toContain(realpath($file));
});
test('excludes directory path without glob wildcards', function (): void {
    $baseDir = sys_get_temp_dir().'/doctest_finder_test_'.uniqid();
    mkdir($baseDir.'/subdir', 0o777, true);
    mkdir($baseDir.'/excluded/nested', 0o777, true);
    file_put_contents($baseDir.'/subdir/keep.md', '# Keep');
    file_put_contents($baseDir.'/excluded/nested/skip.md', '# Skip');

    $files = $this->finder->find([$baseDir], ['excluded']);

    expect($files)->toHaveCount(1);
    $this->assertStringContainsString('keep.md', $files[0]);

    // Cleanup
    unlink($baseDir.'/subdir/keep.md');
    unlink($baseDir.'/excluded/nested/skip.md');
    rmdir($baseDir.'/excluded/nested');
    rmdir($baseDir.'/excluded');
    rmdir($baseDir.'/subdir');
    rmdir($baseDir);
});
test('excludes nested directory path', function (): void {
    $baseDir = sys_get_temp_dir().'/doctest_finder_test_'.uniqid();
    mkdir($baseDir.'/docs/node_modules/pkg', 0o777, true);
    mkdir($baseDir.'/docs/guide', 0o777, true);
    file_put_contents($baseDir.'/docs/guide/intro.md', '# Intro');
    file_put_contents($baseDir.'/docs/node_modules/pkg/README.md', '# Pkg');

    $files = $this->finder->find([$baseDir], ['docs/node_modules']);

    expect($files)->toHaveCount(1);
    $this->assertStringContainsString('intro.md', $files[0]);

    // Cleanup
    unlink($baseDir.'/docs/guide/intro.md');
    unlink($baseDir.'/docs/node_modules/pkg/README.md');
    rmdir($baseDir.'/docs/node_modules/pkg');
    rmdir($baseDir.'/docs/node_modules');
    rmdir($baseDir.'/docs/guide');
    rmdir($baseDir.'/docs');
    rmdir($baseDir);
});
test('excludes directory path with relative input', function (): void {
    $baseDir = sys_get_temp_dir().'/doctest_finder_reltest_'.uniqid();
    mkdir($baseDir.'/docs/node_modules/pkg', 0o777, true);
    mkdir($baseDir.'/docs/guide', 0o777, true);
    file_put_contents($baseDir.'/docs/guide/intro.md', '# Intro');
    file_put_contents($baseDir.'/docs/node_modules/pkg/README.md', '# Pkg');

    $originalDir = getcwd();
    chdir($baseDir);

    try {
        $files = $this->finder->find(['docs'], ['docs/node_modules']);

        expect($files)->toHaveCount(1);
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
});
test('excludes simple directory name with relative input', function (): void {
    $baseDir = sys_get_temp_dir().'/doctest_finder_reltest2_'.uniqid();
    mkdir($baseDir.'/docs/node_modules/pkg', 0o777, true);
    mkdir($baseDir.'/docs/guide', 0o777, true);
    file_put_contents($baseDir.'/docs/guide/intro.md', '# Intro');
    file_put_contents($baseDir.'/docs/node_modules/pkg/README.md', '# Pkg');

    $originalDir = getcwd();
    chdir($baseDir);

    try {
        $files = $this->finder->find(['docs'], ['node_modules']);

        expect($files)->toHaveCount(1);
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
});
test('empty paths returns empty', function (): void {
    $files = $this->finder->find([], []);

    expect($files)->toBe([]);
});
test('directory with no md files returns empty', function (): void {
    $baseDir = sys_get_temp_dir().'/doctest_finder_nomd_'.uniqid();
    mkdir($baseDir, 0o777, true);
    file_put_contents($baseDir.'/readme.txt', 'not markdown');

    $files = $this->finder->find([$baseDir], []);

    expect($files)->toBe([]);

    unlink($baseDir.'/readme.txt');
    rmdir($baseDir);
});
test('excludes by filename pattern', function (): void {
    $baseDir = sys_get_temp_dir().'/doctest_finder_fnmatch_'.uniqid();
    mkdir($baseDir, 0o777, true);
    file_put_contents($baseDir.'/keep.md', '# Keep');
    file_put_contents($baseDir.'/draft-intro.md', '# Draft');

    $files = $this->finder->find([$baseDir], ['draft-*.md']);

    expect($files)->toHaveCount(1);
    $this->assertStringContainsString('keep.md', $files[0]);

    unlink($baseDir.'/keep.md');
    unlink($baseDir.'/draft-intro.md');
    rmdir($baseDir);
});
test('multiple exclude patterns', function (): void {
    $baseDir = sys_get_temp_dir().'/doctest_finder_multi_'.uniqid();
    mkdir($baseDir, 0o777, true);
    file_put_contents($baseDir.'/keep.md', '# Keep');
    file_put_contents($baseDir.'/draft.md', '# Draft');
    file_put_contents($baseDir.'/temp.md', '# Temp');

    $files = $this->finder->find([$baseDir], ['draft.md', 'temp.md']);

    expect($files)->toHaveCount(1);
    $this->assertStringContainsString('keep.md', $files[0]);

    unlink($baseDir.'/keep.md');
    unlink($baseDir.'/draft.md');
    unlink($baseDir.'/temp.md');
    rmdir($baseDir);
});
