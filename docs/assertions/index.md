# Assertions Overview

Assertions tell DocTest what to expect from a code block's execution. Use HTML comments after the code block for invisible assertions, or `// =>` inside the code for inline result checks.

## Assertion Types

| Type | Syntax | Description |
|------|--------|-------------|
| [Output](/assertions/output) | `<!-- doctest: -->` | Exact output match |
| [OutputContains](/assertions/output-contains) | `<!-- doctest-contains: -->` | Partial output match |
| [OutputMatches](/assertions/output-matches) | `<!-- doctest-matches: -->` | Regex pattern match |
| [OutputJson](/assertions/output-json) | `<!-- doctest-json: -->` | JSON structure comparison |
| [Expect](/assertions/expect) | `<!-- doctest-expect: -->` | Expression must be truthy |
| [Result Comment](/assertions/result-comment) | `// =>` | Return value comparison |
| [Debug Dump](/assertions/debug-dump) | `// => dd()` | Inspect value without asserting |

## HTML Comments vs. Result Comments

### HTML Comments

Written after the code block. Invisible in rendered documentation:

````markdown
```php
echo 'Hello';
```
<!-- doctest: Hello -->
````

### Result Comments

Written inside the code block using `// =>`. A natural PHP documentation pattern for showing return values:

```php
$x = 42; // => 42
```

## Multiple Assertions

A single code block can have multiple assertions:

````markdown
```php
$x = 42; // => 42
$y = true; // => true
echo $x;
```
<!-- doctest: 42 -->
<!-- doctest-expect: $y === true -->
````

## No Assertion

Code blocks without assertions still execute. If they produce no error, they pass. This is useful for blocks that simply demonstrate syntax without checking output.

## Wildcards

All output-based assertions support [wildcards](/wildcards/) for dynamic values:

````markdown
```php
echo 'Created at ' . date('Y-m-d');
```
<!-- doctest: Created at {{date}} -->
````
