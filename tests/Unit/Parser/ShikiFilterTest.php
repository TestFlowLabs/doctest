<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\Tests\Unit\Parser;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\DocTest\Parser\ShikiFilter;

final class ShikiFilterTest extends TestCase
{
    private ShikiFilter $filter;

    protected function setUp(): void
    {
        $this->filter = new ShikiFilter();
    }

    #[Test]
    public function strips_code_remove_lines_entirely(): void
    {
        $code = "\$before = 'old'; // [!code --]\n\$after = 'new';";
        $result = $this->filter->filter($code, 'php');

        $this->assertStringNotContainsString('before', $result->code);
        $this->assertStringContainsString("\$after = 'new';", $result->code);
    }

    #[Test]
    public function strips_code_add_marker_but_keeps_code(): void
    {
        $code = "\$after = 'new';  // [!code ++]";
        $result = $this->filter->filter($code, 'php');

        $this->assertStringContainsString("\$after = 'new';", $result->code);
        $this->assertStringNotContainsString('[!code ++]', $result->code);
    }

    #[Test]
    public function strips_line_highlight_from_info_string(): void
    {
        $result = $this->filter->filter('$x = 1;', 'php{1,4-6}');

        $this->assertSame('php', $result->infoString);
    }

    #[Test]
    public function handles_multiple_markers_on_different_lines(): void
    {
        $code = "\$a = 1; // [!code --]\n\$b = 2; // [!code ++]\n\$c = 3;";
        $result = $this->filter->filter($code, 'php');

        $this->assertStringNotContainsString('$a', $result->code);
        $this->assertStringContainsString('$b = 2;', $result->code);
        $this->assertStringContainsString('$c = 3;', $result->code);
        $this->assertStringNotContainsString('[!code', $result->code);
    }

    #[Test]
    public function returns_unchanged_code_when_no_markers(): void
    {
        $code = "\$x = 42;\necho \$x;";
        $result = $this->filter->filter($code, 'php');

        $this->assertSame($code, $result->code);
        $this->assertSame('php', $result->infoString);
    }

    #[Test]
    public function preserves_non_marker_comments(): void
    {
        $code = "// This is a regular comment\n\$x = 1;";
        $result = $this->filter->filter($code, 'php');

        $this->assertStringContainsString('// This is a regular comment', $result->code);
    }
}
