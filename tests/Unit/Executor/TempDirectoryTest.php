<?php

declare(strict_types=1);

use TestFlowLabs\DocTest\Executor\TempDirectory;

test('creates unique directory under system temp', function (): void {
    $tempDir = TempDirectory::create();

    expect($tempDir->path)->toStartWith(sys_get_temp_dir().'/doctest_run_');
    expect(is_dir($tempDir->path))->toBeTrue();

    $tempDir->cleanup();
});

test('creates different directories on each call', function (): void {
    $dir1 = TempDirectory::create();
    $dir2 = TempDirectory::create();

    expect($dir1->path)->not->toBe($dir2->path);

    $dir1->cleanup();
    $dir2->cleanup();
});

test('cleanup removes directory and all contents', function (): void {
    $tempDir = TempDirectory::create();

    // Create some files inside
    file_put_contents($tempDir->path.'/test1.php', '<?php echo 1;');
    file_put_contents($tempDir->path.'/test2.php', '<?php echo 2;');

    expect(file_exists($tempDir->path.'/test1.php'))->toBeTrue();

    $tempDir->cleanup();

    expect(is_dir($tempDir->path))->toBeFalse();
});

test('cleanup is idempotent', function (): void {
    $tempDir = TempDirectory::create();

    $tempDir->cleanup();
    $tempDir->cleanup(); // Should not throw

    expect(is_dir($tempDir->path))->toBeFalse();
});

test('filePath generates path inside temp directory', function (): void {
    $tempDir = TempDirectory::create();

    $path = $tempDir->filePath('test_block');

    expect($path)->toStartWith($tempDir->path.'/');
    expect($path)->toEndWith('.php');

    $tempDir->cleanup();
});
