# Marker Reference

DocTest's `ShikiFilter` processes all Shiki markers before executing code. Every marker follows one of two rules:

1. **Remove the entire line** — the line is not needed for execution
2. **Strip the marker, keep the code** — the code must execute, but the marker comment is removed

## Marker Behavior Table

| Marker | Rendered Docs | DocTest Execution |
|--------|--------------|-------------------|
| `{1,4-6}` | Highlighted lines | Stripped from info string |
| `// [!code --]` | Red diff line | Line removed entirely |
| `// [!code ++]` | Green diff line | Marker stripped, line kept |
| `// [!code hide]` | Line hidden | Marker stripped, line kept |
| `// [!code hide:start]` | Block hidden | Delimiter line removed |
| `// [!code hide:end]` | Block hidden | Delimiter line removed |
| `// [!code highlight]` | Yellow highlight | Marker stripped, line kept |
| `// [!code focus]` | Focused line | Marker stripped, line kept |
| `// [!code warning]` | Warning style | Marker stripped, line kept |
| `// [!code error]` | Error style | Marker stripped, line kept |
| `// [!code word:xxx]` | Word highlight | Marker stripped, line kept |

## Line Highlights `{1,4-6}`

The `{...}` notation is **stripped from the info string** so it doesn't interfere with attribute parsing:

```
php{1,4-6}  →  php
```

## Diff Removal `// [!code --]`

Lines containing `// [!code --]` are **removed entirely**. These represent "old" code that shouldn't execute:

```php ignore
// Before filtering:
$old = 'before'; // [!code --]
$new = 'after';  // [!code ++]

// After filtering:
$new = 'after';
```

## Diff Addition `// [!code ++]`

The `// [!code ++]` marker is **stripped**, but the code is kept:

```php ignore
// Before filtering:
$new = 'after'; // [!code ++]

// After filtering:
$new = 'after';
```

## Single-Line Hide `// [!code hide]`

The `// [!code hide]` marker is **stripped**, but the code is kept — exactly like `++`. This is useful for lines that must run (like `<?php` tags, `use` statements, `require`) but would distract readers:

```php ignore
// Before filtering:
<?php // [!code hide]
require_once 'vendor/autoload.php'; // [!code hide]
use App\Models\User; // [!code hide]

$user = User::find(1);
echo $user->name;

// After filtering:
<?php
require_once 'vendor/autoload.php';
use App\Models\User;

$user = User::find(1);
echo $user->name;
```

All lines are kept for execution. In rendered docs (with the `shiki-hide-lines` transformer), only the non-hidden lines are visible to readers.

## Block Hide `// [!code hide:start]` / `// [!code hide:end]`

For hiding multiple consecutive lines, use block markers. The **delimiter lines are removed entirely**, but all lines between them are kept:

```php ignore
// Before filtering:
$visible = 1;
// [!code hide:start]
$setup_a = 2;
$setup_b = 3;
// [!code hide:end]
$also_visible = 4;

// After filtering:
$visible = 1;
$setup_a = 2;
$setup_b = 3;
$also_visible = 4;
```

Edge cases:
- An unclosed `// [!code hide:start]` removes only the marker line — all following lines are kept
- An orphan `// [!code hide:end]` is simply removed

## Other Markers (highlight, focus, warning, error, word)

All other `// [!code xxx]` markers are **stripped**, and the code is kept. These markers only affect visual rendering and have no impact on execution:

```php ignore
// Before filtering:
$x = 1; // [!code highlight]
$y = 2; // [!code focus]
$z = 3; // [!code warning]

// After filtering:
$x = 1;
$y = 2;
$z = 3;
```
