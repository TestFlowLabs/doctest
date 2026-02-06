# Configuration

DocTest can be configured via a `doctest.php` file in your project root. This file returns an array of options.

## Default Config

If no config file exists, DocTest uses these defaults:

```php
return [
    'paths'     => ['docs', 'README.md'],
    'exclude'   => [],
    'execution' => [
        'timeout'      => 30,
        'memory_limit' => '256M',
    ],
    'output' => [
        'normalize_whitespace' => true,
        'trim_trailing'        => true,
    ],
    'reporters' => [
        'console' => true,
        'junit'   => null,
        'json'    => null,
    ],
];
```

## Options Reference

### `paths`

Array of files and directories to scan for markdown files.

```php
'paths' => ['docs', 'README.md', 'guides/'],
```

### `exclude`

Array of patterns to exclude from scanning.

```php
'exclude' => ['docs/drafts', 'docs/archive'],
```

### `execution.timeout`

Maximum execution time per code block in seconds.

```php
'execution' => [
    'timeout' => 30,
],
```

### `execution.memory_limit`

PHP memory limit for each code block process.

```php
'execution' => [
    'memory_limit' => '256M',
],
```

### `stop_on_failure`

Stop execution at the first failing block.

```php
'stop_on_failure' => true,
```

### `dry_run`

Parse and list blocks without executing.

```php
'dry_run' => true,
```

### `filter`

Filter blocks by content or file name.

```php
'filter' => 'array_map',
```

### `output.normalize_whitespace`

Normalize whitespace in output comparison (default: `true`).

```php
'output' => [
    'normalize_whitespace' => true,
],
```

### `output.trim_trailing`

Trim trailing whitespace from output lines (default: `true`).

```php
'output' => [
    'trim_trailing' => true,
],
```

### `reporters`

Configure output reporters. See [Reporters](/reporters/) for details.

```php
'reporters' => [
    'console' => true,
    'junit'   => 'build/doctest.xml',
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
