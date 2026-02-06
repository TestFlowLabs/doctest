# Output Assertion

The `Output` assertion verifies the **exact output** of a code block.

## Syntax

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

The HTML comment form supports multi-line expected output:

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

````markdown
```php
echo 'Processed 42 items at ' . date('Y-m-d');
```
<!-- doctest: Processed {{int}} items at {{date}} -->
````

## Tips

<div v-pre>

- Output comparison is **exact** (after normalization). Use [OutputContains](/assertions/output-contains) for partial matches.
- Use `{{...}}` wildcard to match arbitrary content in the middle of output.
- The HTML comment form avoids conflicts with PHP syntax.

</div>
