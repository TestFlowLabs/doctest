<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Audit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Assertion\AssertionParser;
use TestFlowLabs\DocTest\Audit\AuditScanner;
use TestFlowLabs\DocTest\CodeBlock\Attributes;
use TestFlowLabs\DocTest\CodeBlock\CodeBlock;

final class AuditScannerTest extends TestCase
{
    private AuditScanner $scanner;

    private AssertionParser $parser;

    protected function setUp(): void
    {
        $this->scanner = new AuditScanner();
        $this->parser = new AssertionParser();
    }

    private function makeBlock(string $code, string $file = 'test.md', int $line = 1): CodeBlock
    {
        $parsed = $this->parser->parse($code);

        return new CodeBlock(
            file: $file,
            startLine: $line,
            rawCode: $code,
            executableCode: $parsed->executableCode,
            attributes: new Attributes(),
            assertions: [],
        );
    }

    #[Test]
    public function flags_filesystem_operations(): void
    {
        $block = $this->makeBlock('file_put_contents("/tmp/test", "data");');
        $findings = $this->scanner->scan([$block]);

        $this->assertNotEmpty($findings);
        $this->assertSame('filesystem', $findings[0]->category);
    }

    #[Test]
    public function flags_network_operations(): void
    {
        $block = $this->makeBlock('$ch = curl_init("https://example.com");');
        $findings = $this->scanner->scan([$block]);

        $this->assertNotEmpty($findings);
        $this->assertSame('network', $findings[0]->category);
    }

    #[Test]
    public function flags_process_execution(): void
    {
        $block = $this->makeBlock('exec("rm -rf /");');
        $findings = $this->scanner->scan([$block]);

        $this->assertNotEmpty($findings);
        $this->assertSame('process', $findings[0]->category);
    }

    #[Test]
    public function finding_includes_file_and_line(): void
    {
        $block = $this->makeBlock('shell_exec("whoami");', 'docs/api.md', 42);
        $findings = $this->scanner->scan([$block]);

        $this->assertNotEmpty($findings);
        $this->assertSame('docs/api.md', $findings[0]->file);
        $this->assertSame(42, $findings[0]->line);
    }

    #[Test]
    public function finding_includes_matched_function(): void
    {
        $block = $this->makeBlock('unlink("/tmp/file");');
        $findings = $this->scanner->scan([$block]);

        $this->assertNotEmpty($findings);
        $this->assertStringContainsString('unlink', $findings[0]->match);
    }

    #[Test]
    public function safe_code_produces_no_findings(): void
    {
        $block = $this->makeBlock('$x = 1 + 2; echo $x;');
        $findings = $this->scanner->scan([$block]);

        $this->assertEmpty($findings);
    }

    #[Test]
    public function scans_multiple_blocks(): void
    {
        $blocks = [
            $this->makeBlock('echo "safe";'),
            $this->makeBlock('exec("ls");', line: 10),
            $this->makeBlock('file_get_contents("/etc/passwd");', line: 20),
        ];

        $findings = $this->scanner->scan($blocks);

        $this->assertCount(2, $findings);
    }
}
