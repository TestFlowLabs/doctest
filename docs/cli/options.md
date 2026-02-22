# Options Reference

Complete reference for all `vendor/bin/doctest` command-line options.

## Arguments

### `files`

Positional arguments specifying markdown files or directories to test.

```bash
# Single file
vendor/bin/doctest README.md

# Multiple files
vendor/bin/doctest docs/getting-started.md docs/api.md

# Directory (scans recursively for .md files)
vendor/bin/doctest docs/

# Mixed
vendor/bin/doctest README.md docs/

# Specific block (Nth PHP block in the file, 1-based)
vendor/bin/doctest README.md:3

# Multiple files with block indices
vendor/bin/doctest README.md:1 docs/api.md:5
```

If no files are specified, DocTest uses the configured paths (default: `docs/` and `README.md`).

The `:N` suffix targets a specific PHP code block within a file. Block numbering is **1-based** (the first PHP block is `:1`). If the block number exceeds the number of PHP blocks in the file, DocTest exits with code 3 (no tests found).

## Options

### `--filter`, `-f`

Filter blocks by content or file name. Only blocks whose code or file path contains the filter string will execute.

```bash
# Only run blocks containing "array_map"
vendor/bin/doctest --filter="array_map"

# Filter by file name
vendor/bin/doctest --filter="getting-started"
```

### `--exclude`

Exclude files matching a pattern.

```bash
# Skip vendor directory
vendor/bin/doctest --exclude vendor

# Skip draft docs
vendor/bin/doctest --exclude drafts
```

### `--dry-run`

Parse and list blocks without executing them. Useful for verifying which blocks DocTest will find.

```bash
vendor/bin/doctest --dry-run
```

**Mutually exclusive** with `--update`.

### `--update`, `-u`

Update outdated assertion values with actual execution output. Works like Jest's `--updateSnapshot` — runs all blocks, detects mismatches in updatable assertions, and rewrites the markdown file.

```bash
# Update all stale assertions
vendor/bin/doctest --update

# Update only a specific file
vendor/bin/doctest docs/api.md --update

# Update a specific block
vendor/bin/doctest docs/api.md:3 --update
```

**Updatable assertion types:**

| Assertion | Updated? | Notes |
|-----------|----------|-------|
| `<!-- doctest: -->` | Yes | Unless it contains wildcards (`{{any}}`, `{{date}}`, etc.) |
| `<!-- doctest-json: -->` | Yes | Always |
| `// => value` | Yes | Always |
| `<!-- doctest-output -->` | Yes | Display block content refreshed |
| `<!-- doctest-contains: -->` | No | Partial match can't be auto-generated |
| `<!-- doctest-matches: -->` | No | Regex can't be auto-generated |
| `<!-- doctest-expect: -->` | No | Expression comparison can't be auto-updated |

**Mutually exclusive** with `--dry-run` and `--stop-on-failure`. If combined, DocTest exits with code `1`.

### `--stop-on-failure`

Stop execution at the first failing block.

```bash
vendor/bin/doctest --stop-on-failure
```

### `--config`, `-c`

Specify a custom config file path (default: `doctest.php` in project root).

```bash
vendor/bin/doctest -c custom-doctest.php
```

### Verbosity

Verbosity is controlled with the standard Symfony Console `-v` flags:

| Flag | Level | Shows |
|------|-------|-------|
| (none) | Normal | Block-level pass/fail only |
| `-v` | Verbose | Per-assertion details under each block |
| `-vv` | Very verbose | Also shows source code on failure |

```bash
# Show assertion details
vendor/bin/doctest -v

# Show assertion details + source code
vendor/bin/doctest -vv
```

## Combining Options

Options can be combined:

```bash
vendor/bin/doctest docs/ --filter="database" --stop-on-failure -v
```
