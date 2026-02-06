<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Watcher;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Watcher\FileWatcher;

final class FileWatcherTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/doctest_watch_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tempDir . '/*') ?: []);
        rmdir($this->tempDir);
    }

    #[Test]
    public function detects_modified_files(): void
    {
        $file = $this->tempDir . '/test.md';
        file_put_contents($file, 'original');

        $watcher = new FileWatcher([$this->tempDir], ['md']);
        $watcher->snapshot();

        sleep(1);
        file_put_contents($file, 'modified');

        $changed = $watcher->getChangedFiles();

        $this->assertContains($file, $changed);
    }

    #[Test]
    public function detects_new_files(): void
    {
        $watcher = new FileWatcher([$this->tempDir], ['md']);
        $watcher->snapshot();

        $newFile = $this->tempDir . '/new.md';
        file_put_contents($newFile, 'content');

        $changed = $watcher->getChangedFiles();

        $this->assertContains($newFile, $changed);
    }

    #[Test]
    public function no_changes_returns_empty(): void
    {
        $file = $this->tempDir . '/test.md';
        file_put_contents($file, 'content');

        $watcher = new FileWatcher([$this->tempDir], ['md']);
        $watcher->snapshot();

        $changed = $watcher->getChangedFiles();

        $this->assertEmpty($changed);
    }

    #[Test]
    public function filters_by_extension(): void
    {
        $mdFile = $this->tempDir . '/doc.md';
        $txtFile = $this->tempDir . '/notes.txt';
        file_put_contents($mdFile, 'doc');
        file_put_contents($txtFile, 'notes');

        $watcher = new FileWatcher([$this->tempDir], ['md']);
        $watcher->snapshot();

        sleep(1);
        file_put_contents($mdFile, 'updated');
        file_put_contents($txtFile, 'updated');

        $changed = $watcher->getChangedFiles();

        $this->assertContains($mdFile, $changed);
        $this->assertNotContains($txtFile, $changed);
    }

    #[Test]
    public function snapshot_resets_change_detection(): void
    {
        $file = $this->tempDir . '/test.md';
        file_put_contents($file, 'original');

        $watcher = new FileWatcher([$this->tempDir], ['md']);
        $watcher->snapshot();

        sleep(1);
        file_put_contents($file, 'modified');

        $this->assertNotEmpty($watcher->getChangedFiles());

        $watcher->snapshot();

        $this->assertEmpty($watcher->getChangedFiles());
    }
}
