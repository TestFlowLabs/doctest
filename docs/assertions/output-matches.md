# OutputMatches Assertion

The `OutputMatches` assertion checks that the output matches a **regular expression** pattern.

## Syntax

````markdown
```php
echo date('Y');
```
<!-- doctest-matches: /^\d{4}$/ -->
````

## How It Works

1. The code block executes and its output is captured
2. The pattern is used with `preg_match()` against the actual output
3. If the regex is invalid, the assertion fails with a descriptive error

## Pattern Format

Patterns must include delimiters, just like PHP's `preg_match()`:

````markdown
```php
echo 'abc123';
```
<!-- doctest-matches: /^[a-z]+\d+$/ -->
````

````markdown
```php
echo 'Hello World';
```
<!-- doctest-matches: /hello world/i -->
````

## When to Use

- When the output has a known structure but variable content
- When you need more precision than `OutputContains` but more flexibility than `Output`
- When validating formats like dates, UUIDs, or version strings

## Examples

### Validate email format

```php
echo 'user@example.com';
```
<!-- doctest-matches: /^[\w.+-]+@[\w-]+\.[\w.]+$/ -->

### Match a version string

```php
echo '2.1.0';
```
<!-- doctest-matches: /^\d+\.\d+\.\d+$/ -->

::: tip
For common dynamic patterns like dates, UUIDs, and integers, consider using [wildcards](/wildcards/) with the `Output` assertion instead. They're more readable.
:::
