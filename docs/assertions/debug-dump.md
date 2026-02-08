# Debug Dump (`// => dd()`)

The debug dump syntax lets you inspect expression values during test execution without affecting pass/fail results. It uses the same `// =>` syntax as [result comments](/assertions/result-comment), but with `dd()` as the expected value.

## Syntax

```php
$expression; // => dd()
```

The expression value is captured and displayed in the test output. The block always passes regardless of the value.

## How It Works

1. The expression on the left side of `// => dd()` is evaluated
2. The result is captured using `var_export()`
3. The value is displayed in the test output with a `dd` marker
4. **No comparison is made** — the block passes as long as no runtime error occurs

## When to Use

- **Debugging** — quickly inspect what an expression returns
- **Exploring** — understand unfamiliar code behavior
- **Prototyping assertions** — see the actual value before writing a `// =>` assertion

## Examples

### Single dump

```php
$x = 42; // => dd()
```

### Multiple dumps in one block

```php
$x = 1; // => dd()
$y = 2; // => dd()
$z = $x + $y; // => dd()
```

### Mixed with assertions

Debug dumps can coexist with regular assertions in the same block. The assertions are checked normally, while the dumps just display values:

```php
$x = 42; // => dd()
$y = 10; // => 10
```

### Function calls

```php
strtoupper("hello"); // => dd()
```

### In group blocks

Debug dumps work in grouped blocks with shared state:

````markdown
```php group="calc"
$base = 100; // => dd()
```

```php group="calc"
$result = $base * 1.2; // => dd()
echo $result;
```
<!-- doctest: 120 -->
````

## Output Format

When running DocTest, debug dumps appear with a `dd` marker:

```
  :1 ✔ $x = 42; // => dd()
       dd $x = 42 => 42
```

The output is always shown regardless of verbosity level (`-v`, `-vv`).

## Comparison with Result Comment

| Feature | `// => value` | `// => dd()` |
|---------|---------------|--------------|
| Compares value | Yes | No |
| Affects pass/fail | Yes | No |
| Shows value in output | Only with `-v` | Always |
| Use case | Verify correctness | Inspect & debug |
