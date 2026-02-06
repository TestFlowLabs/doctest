# Shiki Compatibility

DocTest handles [Shiki](https://shiki.style/) line highlights and diff markers used in VitePress and similar documentation tools.

## What Shiki Adds

VitePress uses Shiki for syntax highlighting and supports special markers in code blocks:

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

## How DocTest Handles Them

DocTest's `ShikiFilter` processes these markers before executing code:

### Line Highlights `{1,4-6}`

The `{...}` notation is **stripped from the info string** so it doesn't interfere with attribute parsing:

```
```php{1,4-6}  →  ```php
```

### Diff Removal `// [!code --]`

Lines containing `// [!code --]` are **removed entirely**. These represent "old" code that shouldn't execute:

```php
// Before filtering:
$old = 'before'; // [!code --]
$new = 'after';  // [!code ++]

// After filtering:
$new = 'after';
```

### Diff Addition `// [!code ++]`

The `// [!code ++]` marker is **stripped**, but the code is kept:

```php
// Before filtering:
$new = 'after'; // [!code ++]

// After filtering:
$new = 'after';
```

## Practical Example

You can write VitePress documentation with diff markers and DocTest will execute the "after" version:

````markdown
```php
$greeting = 'Hello';           // [!code --]
$greeting = 'Hello, World!';   // [!code ++]
echo $greeting;
```
<!-- doctest: Hello, World! -->
````

This renders as a nice diff in VitePress while DocTest correctly tests the updated code.

## No Configuration Needed

Shiki filtering is always active. It runs automatically during the parsing phase before any execution occurs.
