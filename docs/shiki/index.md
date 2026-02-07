# Shiki Compatibility

DocTest handles [Shiki](https://shiki.style/) line highlights, diff markers, and hide markers used in any Shiki-powered documentation tool — VitePress, Astro, Nuxt Content, Slidev, or direct Shiki usage.

## What Shiki Adds

Shiki-powered documentation tools support special markers in code blocks:

### Line Highlights

Curly braces in the fence info string highlight specific lines:

````markdown
```php{1,4-6}
$a = 1;
$b = 2;
$c = 3;
$d = 4;
$e = 5;
$f = 6;
```
````

### Diff Markers

Comments mark lines as added or removed:

````markdown
```php
$old = 'before'; // [!code --]
$new = 'after';  // [!code ++]
```
````

### Hide Markers

Hide lines from rendered output while keeping them for execution:

````markdown
```php
<?php // [!code hide]
require_once 'vendor/autoload.php'; // [!code hide]

$user = User::find(1);
echo $user->name;
```
````

Or hide a block of lines:

````markdown
```php
// [!code hide:start]
<?php
declare(strict_types=1);
require_once 'vendor/autoload.php';
use App\Models\User;
// [!code hide:end]

$user = User::find(1);
echo $user->name;
```
````

### Other Visual Markers

Shiki also supports `highlight`, `focus`, `warning`, `error`, and `word:xxx` markers for visual styling in rendered docs.

::: tip Want to know how DocTest processes each marker?
See the [Marker Reference](/shiki/markers) for the complete technical breakdown of what ShikiFilter does with every marker type.
:::

## Practical Examples

### Diff Testing

Write VitePress documentation with diff markers and DocTest will execute the "after" version:

````markdown
```php
$greeting = 'Hello';           // [!code --]
$greeting = 'Hello, World!';   // [!code ++]
echo $greeting;
```
<!-- doctest: Hello, World! -->
````

This renders as a nice diff in VitePress while DocTest correctly tests the updated code.

### Hidden Boilerplate

Hide setup code that readers don't need to see:

````markdown
```php
<?php // [!code hide]
require_once 'vendor/autoload.php'; // [!code hide]

echo 'Hello from DocTest!';
```
<!-- doctest: Hello from DocTest! -->
````

Readers see only the meaningful code. DocTest executes the full snippet including the hidden lines.

### Hidden Block Setup

Hide a larger setup block:

````markdown
```php
// [!code hide:start]
<?php
declare(strict_types=1);
require_once 'vendor/autoload.php';
use App\Support\Collection;
// [!code hide:end]

$items = Collection::make([1, 2, 3]);
echo $items->sum();
```
<!-- doctest: 6 -->
````

## Rendering Hidden Lines

To render hide markers visually in the browser (collapsed placeholders, expand/collapse toggle), use the companion npm package:

[shiki-hide-lines documentation &rarr;](/shiki-hide-lines/)

## No Configuration Needed

Shiki filtering is always active in DocTest. It runs automatically during the parsing phase before any execution occurs. No configuration is needed — all `// [!code xxx]` markers are handled transparently.
