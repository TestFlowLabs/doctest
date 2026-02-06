# Exit Codes

DocTest uses standard exit codes compatible with CI systems.

## Exit Codes

| Code | Constant | Meaning |
|------|----------|---------|
| `0` | `Command::SUCCESS` | All blocks passed (or were skipped) |
| `1` | `Command::FAILURE` | One or more blocks failed |
| `3` | — | No files found or no testable blocks extracted |

## CI Usage

Use the exit code to fail CI builds when documentation tests break:

```bash
vendor/bin/doctest || exit 1
```

Or simply:

```bash
vendor/bin/doctest
```

Most CI systems treat any non-zero exit code as a failure.

## Verbosity in CI

For CI environments, the default verbosity is usually sufficient. Use `-v` for more detailed output in logs:

```bash
vendor/bin/doctest -v
```

## Combining with Reporters

Use the [JSON reporter](/reporters/json) for machine-readable test results:

Create a `doctest.php` config:

```php ignore
return [
    'reporters' => [
        'console' => true,
        'json'    => 'build/doctest.json',
    ],
];
```
