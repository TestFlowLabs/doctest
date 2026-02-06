# Assertions Overview

Assertions tell DocTest what to expect from a code block's execution. You can use inline PHP comments inside the code block, or HTML comments after it.

## Assertion Types

| Type | Syntax | Location | Description |
|------|--------|----------|-------------|
| [Output](/assertions/output) | `// Output:` | Inline | Exact output match |
| [OutputContains](/assertions/output-contains) | `// OutputContains:` | Inline | Partial output match |
| [OutputMatches](/assertions/output-matches) | `// OutputMatches:` | Inline | Regex pattern match |
| [OutputJson](/assertions/output-json) | `// OutputJson:` | Inline | JSON structure comparison |
| [Expect](/assertions/expect) | `// Expect:` | Inline | Expression must be truthy |
| [Result Comment](/assertions/result-comment) | `// =>` | Inline | Return value comparison |
| [HTML Comment](/assertions/html-comment) | `<!-- doctest: -->` | After block | All types via HTML comments |

## Inline vs. HTML Comments

### Inline Comments

Written inside the code block as PHP comments. Visible in rendered documentation:

```php
echo 'Hello';
// Output: Hello
```

### HTML Comments

Written after the code block. Invisible in rendered documentation:

````markdown
```php
echo 'Hello';
```
<!-- doctest: Hello -->
````

Both approaches are equivalent. HTML comments are recommended when you want clean rendered output.

## Multiple Assertions

A single code block can have multiple assertions:

```php
$x = 42; // => 42
$y = true; // => true
echo $x;
// Output: 42
// Expect: $y === true
```

## No Assertion

Code blocks without assertions still execute. If they produce no error, they pass. This is useful for blocks that simply demonstrate syntax without checking output.

## Wildcards

All output-based assertions support [wildcards](/wildcards/) for dynamic values:

```php
echo 'Created at ' . date('Y-m-d');
// Output: Created at {{date}}
```
