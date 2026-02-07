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

// --- hide single-line marker tests (doctest-6v3t) ---

test('strips hide marker but keeps code', function (): void {
    $code   = '$x = 1; // [!code hide]';
    $result = $this->filter->filter($code, 'php');

    expect($result->code)->toBe('$x = 1;');
});

test('strips hide marker with code before marker', function (): void {
    $code   = '<?php // [!code hide]';
    $result = $this->filter->filter($code, 'php');

    expect($result->code)->toBe('<?php');
});

test('hide marker at line start is stripped', function (): void {
    $code   = "// [!code hide]\n\$x = 1;";
    $result = $this->filter->filter($code, 'php');

    $this->assertStringNotContainsString('[!code hide]', $result->code);
    $this->assertStringContainsString('$x = 1;', $result->code);
});

// --- hide:start / hide:end block tests (doctest-bek7) ---

test('removes hide:start and hide:end lines but keeps inner lines', function (): void {
    $code = implode("\n", [
        '$visible = 1;',
        '// [!code hide:start]',
        '$hidden_but_kept = 2;',
        '// [!code hide:end]',
        '$also_visible = 3;',
    ]);
    $result = $this->filter->filter($code, 'php');

    expect($result->code)->toBe("\$visible = 1;\n\$hidden_but_kept = 2;\n\$also_visible = 3;");
});

test('handles multiple hide blocks', function (): void {
    $code = implode("\n", [
        '$a = 1;',
        '// [!code hide:start]',
        '$b = 2;',
        '// [!code hide:end]',
        '$c = 3;',
        '// [!code hide:start]',
        '$d = 4;',
        '// [!code hide:end]',
        '$e = 5;',
    ]);
    $result = $this->filter->filter($code, 'php');

    expect($result->code)->toBe("\$a = 1;\n\$b = 2;\n\$c = 3;\n\$d = 4;\n\$e = 5;");
});

test('handles empty hide block', function (): void {
    $code = implode("\n", [
        '$a = 1;',
        '// [!code hide:start]',
        '// [!code hide:end]',
        '$b = 2;',
    ]);
    $result = $this->filter->filter($code, 'php');

    expect($result->code)->toBe("\$a = 1;\n\$b = 2;");
});

// --- catch-all marker stripping tests (doctest-1dan) ---

test('strips highlight marker but keeps code', function (): void {
    $code   = '$x = 1; // [!code highlight]';
    $result = $this->filter->filter($code, 'php');

    expect($result->code)->toBe('$x = 1;');
});

test('strips focus marker but keeps code', function (): void {
    $code   = '$x = 1; // [!code focus]';
    $result = $this->filter->filter($code, 'php');

    expect($result->code)->toBe('$x = 1;');
});

test('strips warning marker but keeps code', function (): void {
    $code   = '$x = 1; // [!code warning]';
    $result = $this->filter->filter($code, 'php');

    expect($result->code)->toBe('$x = 1;');
});

test('strips error marker but keeps code', function (): void {
    $code   = '$x = 1; // [!code error]';
    $result = $this->filter->filter($code, 'php');

    expect($result->code)->toBe('$x = 1;');
});

test('strips word highlight marker but keeps code', function (): void {
    $code   = '$x = 1; // [!code word:xxx]';
    $result = $this->filter->filter($code, 'php');

    expect($result->code)->toBe('$x = 1;');
});

// --- mixed markers tests (doctest-9zx9) ---

test('handles hide with -- and ++ and highlight on different lines', function (): void {
    $code = implode("\n", [
        '$removed = 0; // [!code --]',
        '$added = 1; // [!code ++]',
        '$hidden = 2; // [!code hide]',
        '$highlighted = 3; // [!code highlight]',
        '$normal = 4;',
    ]);
    $result = $this->filter->filter($code, 'php');

    expect($result->code)->toBe("\$added = 1;\n\$hidden = 2;\n\$highlighted = 3;\n\$normal = 4;");
});

test('preserves line order after filtering with all marker types', function (): void {
    $code = implode("\n", [
        '$a = 1; // [!code highlight]',
        '// [!code hide:start]',
        '$b = 2;',
        '// [!code hide:end]',
        '$c = 3; // [!code --]',
        '$d = 4; // [!code hide]',
        '$e = 5; // [!code focus]',
    ]);
    $result = $this->filter->filter($code, 'php');

    expect($result->code)->toBe("\$a = 1;\n\$b = 2;\n\$d = 4;\n\$e = 5;");
});

// --- edge case tests (doctest-k0je) ---

test('unclosed hide:start removes only the marker line', function (): void {
    $code = implode("\n", [
        '$a = 1;',
        '// [!code hide:start]',
        '$b = 2;',
        '$c = 3;',
    ]);
    $result = $this->filter->filter($code, 'php');

    expect($result->code)->toBe("\$a = 1;\n\$b = 2;\n\$c = 3;");
});

test('orphan hide:end is removed', function (): void {
    $code = implode("\n", [
        '$a = 1;',
        '// [!code hide:end]',
        '$b = 2;',
    ]);
    $result = $this->filter->filter($code, 'php');

    expect($result->code)->toBe("\$a = 1;\n\$b = 2;");
});
