# Configuration

DocTest can be configured via a `doctest.php` file in your project root. This file returns an array of options.

## Default Config

If no config file exists, DocTest uses these defaults:

```php ignore
return [
    'paths'          => ['docs', 'README.md'],
    'exclude'        => [],
    'bootstrap'      => null,
    'bootstraps_dir' => '.doctest',
    'execution'      => [
        'timeout'      => 30,
        'memory_limit' => '256M',
        'parallel'     => 1,
    ],
    'output' => [
        'normalize_whitespace' => true,
        'trim_trailing'        => true,
    ],
    'reporters' => [
        'console' => true,
        'json'    => null,
    ],
];
```

## Options Reference

### `paths`

Array of files and directories to scan for markdown files.

```php ignore
'paths' => ['docs', 'README.md', 'guides/'],
```

### `exclude`

Array of patterns to exclude from scanning.

```php ignore
'exclude' => ['docs/drafts', 'docs/archive'],
```

### `execution.timeout`

Maximum execution time per code block in seconds.

```php ignore
'execution' => [
    'timeout' => 30,
],
```

### `execution.memory_limit`

PHP memory limit for each code block process.

```php ignore
'execution' => [
    'memory_limit' => '256M',
],
```

### `execution.parallel`

Number of parallel worker processes. Default `1` (sequential). Set higher to run code blocks concurrently.

```php ignore
'execution' => [
    'parallel' => 4,
],
```

See [Parallel Execution](/advanced/parallel-execution) for details.

### `bootstrap`

Global PHP code loaded before every code block. Typically used for Composer autoloader or common setup.

```php ignore
'bootstrap' => "require_once __DIR__.'/vendor/autoload.php';",
```

### `bootstraps_dir`

Directory containing bootstrap profile files (default: `.doctest`). Each `.php` file in this directory becomes a profile that blocks can load via `bootstrap="name"`.

```php ignore
'bootstraps_dir' => '.doctest',
```

See [bootstrap attribute](/attributes/bootstrap) for details on per-block bootstrap profiles.

### `stop_on_failure`

Stop execution at the first failing block.

```php ignore
'stop_on_failure' => true,
```

### `dry_run`

Parse and list blocks without executing.

```php ignore
'dry_run' => true,
```

### `filter`

Filter blocks by content or file name.

```php ignore
'filter' => 'array_map',
```

### `verbosity`

Set the default verbosity level (default: `0`). Equivalent to passing `-v` or `-vv` on the CLI.

| Value | Equivalent | Description |
|-------|-----------|-------------|
| `0` | (default) | Block-level pass/fail only |
| `1` | `-v` | Show per-assertion details |
| `2` | `-vv` | Also show source code on failure |

```php ignore
'verbosity' => 1,
```

### `output.normalize_whitespace`

Normalize whitespace in output comparison (default: `true`).

```php ignore
'output' => [
    'normalize_whitespace' => true,
],
```

### `output.trim_trailing`

Trim trailing whitespace from output lines (default: `true`).

```php ignore
'output' => [
    'trim_trailing' => true,
],
```

### `reporters`

Configure output reporters. See [Reporters](/reporters/) for details.

```php ignore
'reporters' => [
    'console' => true,
    'json'    => 'build/doctest.json',
],
```

## Custom Config Path

Use the `--config` CLI option to specify a different config file:

```bash
vendor/bin/doctest -c tests/doctest.php
```

## Config Loading

DocTest loads configuration in this order:

1. Default values
2. `doctest.php` in project root (or custom path via `--config`)
3. CLI options override config file values
