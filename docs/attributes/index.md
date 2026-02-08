# Attributes Overview

Attributes control how DocTest handles each code block. They can be written in two ways:

1. **Info string** — after `php` in the code fence (traditional)
2. **[HTML comment](/attributes/html-comment-syntax)** — in a `<!-- doctest-attr: ... -->` comment before the block (preserves editor syntax highlighting)

## Syntax

Attributes appear after the language identifier in the code fence:

````markdown
```php ignore
// This block is skipped
```

```php throws(RuntimeException)
throw new RuntimeException('Error');
```

```php group="database" setup
$pdo = new PDO('sqlite::memory:');
```
````

## Available Attributes

| Attribute | Description | Example |
|-----------|-------------|---------|
| [`ignore`](/attributes/ignore) | Skip the block entirely | `php ignore` |
| [`no_run`](/attributes/no-run) | Syntax check only, don't execute | `php no_run` |
| [`throws`](/attributes/throws) | Expect an exception | `php throws(RuntimeException)` |
| [`parse_error`](/attributes/parse-error) | Expect a parse error | `php parse_error` |
| [`group`](/attributes/group) | Group blocks sharing state | `php group="name"` |
| [`setup`](/attributes/setup-teardown) | Setup code for a group | `php setup group="name"` |
| [`teardown`](/attributes/setup-teardown) | Teardown code for a group | `php teardown group="name"` |
| [`bootstrap`](/attributes/bootstrap) | Load bootstrap profiles | `php bootstrap="laravel"` |

## Combining Attributes

Some attributes can be combined:

````markdown
```php setup group="database"
// Both setup and group
```

```php teardown group="database"
// Both teardown and group
```
````

## Alternative: HTML Comment Syntax

All attributes above can also be specified via HTML comments before the code block. This preserves editor syntax highlighting. See the [HTML Comment Syntax](/attributes/html-comment-syntax) page for details.

````markdown
<!-- doctest-attr: group="database" setup -->
```php
$pdo = new PDO('sqlite::memory:');
```
````

## Processing Order

When a block has attributes, DocTest processes them in this order:

1. **ignore** — Skip immediately, no further processing
2. **no_run** — Syntax check only (`php -l`)
3. **parse_error** — Execute and expect non-zero exit code
4. **throws** — Execute with try/catch wrapper
5. **group/setup/teardown** — Group execution with shared state
