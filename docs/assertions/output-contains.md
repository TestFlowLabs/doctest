# OutputContains Assertion

The `OutputContains` assertion checks that the output **contains** a given substring.

## Syntax

````markdown
```php
echo 'The quick brown fox jumps over the lazy dog';
```
<!-- doctest-contains: brown fox -->
````

## How It Works

1. The code block executes and its output is captured
2. A simple `str_contains()` check determines if the expected substring appears in the actual output
3. The check is case-sensitive

## When to Use

- When you only care about a specific part of the output
- When the full output is too long or variable to match exactly
- When surrounding output may change but the key content stays the same

## Examples

### Checking for a key in JSON output

```php
echo json_encode(['name' => 'DocTest', 'version' => '1.0']);
```
<!-- doctest-contains: DocTest -->

### Matching part of a formatted string

```php
printf('Hello %s, you have %d messages', 'Alice', 5);
```
<!-- doctest-contains: you have 5 messages -->
