# Reporters Overview

DocTest supports multiple output reporters that can run simultaneously. Configure them in `doctest.php`.

## Available Reporters

| Reporter | Output | Use Case |
|----------|--------|----------|
| [Console](/reporters/console) | Terminal | Interactive development |
| [JUnit XML](/reporters/junit) | `.xml` file | CI dashboards |
| [JSON](/reporters/json) | `.json` file | Custom tooling, analysis |

## Configuration

Enable reporters in your `doctest.php`:

```php
return [
    'reporters' => [
        'console' => true,
        'junit'   => 'build/doctest.xml',
        'json'    => 'build/doctest.json',
    ],
];
```

- **Console** is enabled by default. Set to `false` to disable.
- **JUnit** and **JSON** are disabled by default. Set to a file path to enable.

## Multiple Reporters

All three reporters can run simultaneously. Results are written after all blocks have executed.

```php
return [
    'reporters' => [
        'console' => true,                // Terminal output
        'junit'   => 'build/doctest.xml', // For CI
        'json'    => 'build/doctest.json', // For tooling
    ],
];
```
