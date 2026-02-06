# Output Assertion

The `Output` assertion verifies the **exact output** of a code block.

## Syntax

### Inline Comment

```php
echo 'Hello, World!';
// Output: Hello, World!
```

### HTML Comment

````markdown
```php
echo 'Hello, World!';
```
<!-- doctest: Hello, World! -->
````

## How It Works

1. The code block executes in an isolated process
2. All output (`echo`, `print`, etc.) is captured via `ob_start()` / `ob_get_clean()`
3. The captured output is compared against the expected value
4. Output is normalized: trailing whitespace is trimmed, line endings are unified

## Multi-line Output

Both inline and HTML comment forms support multi-line expected output:

````markdown
```php
echo "line 1\nline 2\nline 3";
```
<!-- doctest: line 1
line 2
line 3 -->
````

## Wildcards

Use [wildcards](/wildcards/) for dynamic portions of the output:

```php
echo 'Processed 42 items at ' . date('Y-m-d');
// Output: Processed {{int}} items at {{date}}
```

## Tips

<div v-pre>

- Output comparison is **exact** (after normalization). Use [OutputContains](/assertions/output-contains) for partial matches.
- Use `{{...}}` wildcard to match arbitrary content in the middle of output.
- If your output contains special characters, the HTML comment form avoids conflicts with PHP syntax.

</div>
