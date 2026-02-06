# Result Comment Assertion

The result comment assertion uses `// =>` at the end of a line to verify the **value** of the expression on that line.

## Syntax

```php
$x = 42; // => 42
$flag = true; // => true
$nothing = null; // => NULL
```

## How It Works

1. The expression on the left side of `// =>` is evaluated
2. The result is converted to a string using `var_export()`
3. The `var_export` output is compared against the expected value on the right

Since `var_export` is used, the expected values follow PHP's `var_export` format:

| PHP Value | var_export Output | Expected |
|-----------|-------------------|----------|
| `42` | `42` | `42` |
| `3.14` | `3.14` | `3.14` |
| `true` | `true` | `true` |
| `false` | `false` | `false` |
| `null` | `NULL` | `NULL` |
| `'hello'` | `'hello'` | `'hello'` |
| `[1, 2]` | `array (0 => 1, 1 => 2,)` | `array (...)` |

## When to Use

- When documenting APIs where return values matter
- For inline demonstrations of expressions and their results
- When you want a compact, readable assertion format

## Examples

### Arithmetic

```php
$a = 10 + 5; // => 15
$b = 10 * 3; // => 30
```

### String functions

```php
$upper = strtoupper('hello'); // => 'HELLO'
$len = strlen('test'); // => 4
```

### Boolean checks

```php
$empty = empty([]); // => true
$isset = isset($undefined); // => false
```

## Regular Comments

Regular PHP comments (without `=>`) are left untouched. Only the `// =>` pattern triggers an assertion:

````markdown
```php
$x = 42; // This is just a comment, not an assertion
$y = 42; // => 42   <-- This IS an assertion
```
````
