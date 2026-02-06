<?php

declare(strict_types=1);
use TestFlowLabs\DocTest\Parser\ShikiFilter;

beforeEach(function (): void {
    $this->filter = new ShikiFilter();
});
test('strips code remove lines entirely', function (): void {
    $code   = "\$before = 'old'; // [!code --]\n\$after = 'new';";
    $result = $this->filter->filter($code, 'php');

    $this->assertStringNotContainsString('before', $result->code);
    $this->assertStringContainsString("\$after = 'new';", $result->code);
});
test('strips code add marker but keeps code', function (): void {
    $code   = "\$after = 'new';  // [!code ++]";
    $result = $this->filter->filter($code, 'php');

    $this->assertStringContainsString("\$after = 'new';", $result->code);
    $this->assertStringNotContainsString('[!code ++]', $result->code);
});
test('strips line highlight from info string', function (): void {
    $result = $this->filter->filter('$x = 1;', 'php{1,4-6}');

    expect($result->infoString)->toBe('php');
});
test('handles multiple markers on different lines', function (): void {
    $code   = "\$a = 1; // [!code --]\n\$b = 2; // [!code ++]\n\$c = 3;";
    $result = $this->filter->filter($code, 'php');

    $this->assertStringNotContainsString('$a', $result->code);
    $this->assertStringContainsString('$b = 2;', $result->code);
    $this->assertStringContainsString('$c = 3;', $result->code);
    $this->assertStringNotContainsString('[!code', $result->code);
});
test('returns unchanged code when no markers', function (): void {
    $code   = "\$x = 42;\necho \$x;";
    $result = $this->filter->filter($code, 'php');

    expect($result->code)->toBe($code);
    expect($result->infoString)->toBe('php');
});
test('preserves non marker comments', function (): void {
    $code   = "// This is a regular comment\n\$x = 1;";
    $result = $this->filter->filter($code, 'php');

    $this->assertStringContainsString('// This is a regular comment', $result->code);
});
test('handles empty code', function (): void {
    $result = $this->filter->filter('', 'php');

    expect($result->code)->toBe('');
    expect($result->infoString)->toBe('php');
});
test('handles empty info string', function (): void {
    $result = $this->filter->filter('$x = 1;', '');

    expect($result->code)->toBe('$x = 1;');
    expect($result->infoString)->toBe('');
});
test('all lines removed produces empty code', function (): void {
    $code   = "\$a = 1; // [!code --]\n\$b = 2; // [!code --]";
    $result = $this->filter->filter($code, 'php');

    expect($result->code)->toBe('');
});
test('strips multiple highlight groups from info string', function (): void {
    $result = $this->filter->filter('$x = 1;', 'php{1,3}{5-7}');

    expect($result->infoString)->toBe('php');
});
test('info string without highlight notation unchanged', function (): void {
    $result = $this->filter->filter('$x = 1;', 'php title="example.php"');

    expect($result->infoString)->toBe('php title="example.php"');
});
test('code add marker at line start is stripped', function (): void {
    $code   = "// [!code ++]\n\$x = 1;";
    $result = $this->filter->filter($code, 'php');

    $this->assertStringNotContainsString('[!code ++]', $result->code);
    $this->assertStringContainsString('$x = 1;', $result->code);
});
test('preserves line order after filtering', function (): void {
    $code   = "\$a = 1;\n\$b = 2; // [!code --]\n\$c = 3; // [!code ++]\n\$d = 4;";
    $result = $this->filter->filter($code, 'php');

    expect($result->code)->toBe("\$a = 1;\n\$c = 3;\n\$d = 4;");
});
test('highlight notation with range stripped', function (): void {
    $result = $this->filter->filter('echo "hi";', 'php{2-5}');

    expect($result->infoString)->toBe('php');
});
