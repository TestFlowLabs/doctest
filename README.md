# DocTest

A PHP documentation testing tool that validates code examples in your markdown files. Write documentation with confidence — if it compiles and runs, it stays correct.

## Why?

Code examples in documentation rot. APIs change, methods get renamed, return types evolve — but the docs stay frozen. DocTest extracts PHP code blocks from your markdown files, executes them in isolated processes, and verifies their output. If an example breaks, you'll know immediately.

- Code examples are tested on every CI run
- Output assertions catch regressions automatically
- Process isolation means examples can't interfere with each other or your test suite

## Installation

```bash
composer require --dev testflowlabs/doctest
```

## Quick Start

Write a PHP code block in any markdown file with an `Output:` comment:

````markdown
```php
echo 'Hello, World!';
// Output: Hello, World!
```
````

Run it:

```bash
vendor/bin/doctest
```

DocTest scans `docs/` and `README.md` by default. That's it.

## Assertions

### Exact Output

```php
echo 2 + 3;
// Output: 5
```

### Contains

```php
echo 'The quick brown fox jumps over the lazy dog';
// OutputContains: brown fox
```

### Regex

```php
echo date('Y');
// OutputMatches: /^\d{4}$/
```

### JSON

```php
echo json_encode(['name' => 'DocTest', 'php' => '8.4+']);
// OutputJson: {"name": "DocTest", "php": "8.4+"}
```

### Expression

```php
$result = array_sum([1, 2, 3, 4, 5]);
// Expect: $result === 15
```

## Wildcards

When output contains dynamic values, use wildcards:

```php
echo 'Request took 42ms at ' . date('Y-m-d');
// Output: Request took {{int}}ms at {{date}}
```

Available wildcards: `{{any}}`, `{{int}}`, `{{float}}`, `{{uuid}}`, `{{date}}`, `{{time}}`, `{{datetime}}`, `{{...}}`

## Attributes

Control how code blocks are handled via the fence info string:

````markdown
```php ignore
// This block won't be executed
$config = require 'missing-file.php';
```

```php throws(InvalidArgumentException)
throw new InvalidArgumentException('Expected an integer');
```

```php no_run
// Syntax is checked but code is not executed
$db->query('SELECT * FROM users');
```
````

## Groups with Setup/Teardown

Share state across related examples:

````markdown
```php setup group="database"
$pdo = new PDO('sqlite::memory:');
$pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
```

```php group="database"
$pdo->exec("INSERT INTO users (name) VALUES ('Alice')");
$count = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
echo $count;
// Output: 1
```

```php teardown group="database"
$pdo->exec('DROP TABLE users');
```
````

## CLI

```bash
# Test specific files
vendor/bin/doctest docs/getting-started.md docs/api.md

# Filter by content
vendor/bin/doctest --filter="array_sum"

# Dry run (parse only, don't execute)
vendor/bin/doctest --dry-run

# Stop on first failure
vendor/bin/doctest --stop-on-failure

# Increase verbosity
vendor/bin/doctest -v    # show block details
vendor/bin/doctest -vv   # show generated code
vendor/bin/doctest -vvv  # show full debug output
```

## Configuration

Create a `doctest.php` in your project root:

```php no_run
<?php

return [
    'paths'     => ['docs', 'README.md'],
    'exclude'   => ['docs/drafts'],
    'execution' => [
        'timeout'      => 30,
        'memory_limit' => '256M',
    ],
    'reporters' => [
        'console' => true,
        'junit'   => 'build/doctest.xml',
        'json'    => 'build/doctest.json',
    ],
];
```

## CI Integration

Add to your GitHub Actions workflow:

```yaml
- name: DocTest
  run: vendor/bin/doctest
```

## Laravel

DocTest integrates with Laravel automatically. After installing, an Artisan command is available:

```bash
php artisan doctest
```

## Requirements

- PHP 8.4+

## License

MIT
