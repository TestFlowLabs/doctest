# Artisan Command

DocTest registers a `doctest` Artisan command when installed in a Laravel project.

## Usage

```bash
php artisan doctest
```

## Available Options

The Artisan command supports the same options as the standalone CLI:

```bash
# Test specific files
php artisan doctest docs/getting-started.md

# Filter by content
php artisan doctest --filter="eloquent"

# Dry run
php artisan doctest --dry-run
```

| Option | Short | Description |
|--------|-------|-------------|
| `files` | | Positional: files or directories to test |
| `--filter` | `-f` | Filter blocks by content |
| `--dry-run` | | Parse only, don't execute |

## Artisan vs. Standalone

| Feature | `php artisan doctest` | `vendor/bin/doctest` |
|---------|----------------------|---------------------|
| Laravel bootstrap | Automatic | Manual (via config) |
| All CLI options | Subset | Full set |
| Non-Laravel projects | Not available | Available |

For full CLI options (like `--stop-on-failure`, `--exclude`, `--config`), use `vendor/bin/doctest`.
