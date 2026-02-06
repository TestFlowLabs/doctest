# HTML Comment Assertions

HTML comments are the primary way to add assertions in DocTest. They're invisible in rendered documentation, keeping your examples clean.

## Syntax

HTML comment assertions are placed **immediately after** the code block:

````markdown
```php
echo 'Hello';
```
<!-- doctest: Hello -->
````

## Available Types

| HTML Comment | Assertion Type | Description |
|---|---|---|
| `<!-- doctest: value -->` | [Output](/assertions/output) | Exact output match |
| `<!-- doctest-contains: value -->` | [OutputContains](/assertions/output-contains) | Partial output match |
| `<!-- doctest-matches: /pattern/ -->` | [OutputMatches](/assertions/output-matches) | Regex pattern match |
| `<!-- doctest-json: {...} -->` | [OutputJson](/assertions/output-json) | JSON structure comparison |
| `<!-- doctest-expect: expr -->` | [Expect](/assertions/expect) | Expression must be truthy |

## Parsing Rules

- The comment must start with `<!--` and end with `-->`
- The keyword (`doctest`, `doctest-contains`, etc.) is case-sensitive
- Whitespace around the value is trimmed
- Multi-line values are supported

## Multi-line Values

For multi-line expected output, the value spans across lines within the HTML comment:

````markdown
```php
echo "line 1\nline 2\nline 3";
```
<!-- doctest: line 1
line 2
line 3 -->
````

## Wildcards

HTML comment assertions support [wildcards](/wildcards/):

````markdown
```php
echo 'Generated ID: ' . uniqid();
```
<!-- doctest: Generated ID: {{any}} -->
````

## Why HTML Comments?

1. **Clean rendering** — Readers see only the code, not the test
2. **No PHP syntax conflicts** — The assertion is outside the PHP code block
3. **Works with any renderer** — GitHub, VitePress, GitBook, etc. all hide HTML comments
4. **Same power** — All assertion types are available
