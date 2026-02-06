# Reporters Overview

DocTest supports multiple output reporters that can run simultaneously. Configure them in `doctest.php`.

## Available Reporters

| Reporter | Output | Use Case |
|----------|--------|----------|
| [Console](/reporters/console) | Terminal | Interactive development |
| [JSON](/reporters/json) | `.json` file | Custom tooling, analysis |

## Configuration

Enable reporters in your `doctest.php`:

```php ignore
return [
    'reporters' => [
        'console' => true,
        'json'    => 'build/doctest.json',
    ],
];
```

- **Console** is enabled by default. Set to `false` to disable.
- **JSON** is disabled by default. Set to a file path to enable.

## Multiple Reporters

Both reporters can run simultaneously. Results are written after all blocks have executed.

```php ignore
return [
    'reporters' => [
        'console' => true,                // Terminal output
        'json'    => 'build/doctest.json', // For tooling
    ],
];
```
