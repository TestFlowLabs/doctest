# CLI Usage

DocTest provides a command-line interface via `vendor/bin/doctest`.

## Basic Usage

```bash
# Run with default paths (docs/ and README.md)
vendor/bin/doctest

# Test specific files
vendor/bin/doctest docs/getting-started.md docs/api.md

# Test a directory
vendor/bin/doctest docs/

# Test only the 3rd PHP block in a file
vendor/bin/doctest README.md:3
```

## Quick Reference

```bash
vendor/bin/doctest [files...] [options]
```

| Option | Short | Description |
|--------|-------|-------------|
| `--filter` | `-f` | Filter blocks by content or file name |
| `--exclude` | | Exclude files matching pattern |
| `--dry-run` | | Parse only, don't execute |
| `--update` | `-u` | Update stale assertions with actual output |
| `--stop-on-failure` | | Stop on first failure |
| `--parallel` | `-p` | Run blocks in parallel (auto-detects cores) |
| `--config` | `-c` | Path to config file |
| `-v` | | Show per-assertion details |
| `-vv` | | Also show source code on failure |

See [Options Reference](/cli/options) for full details.

## Console Output

DocTest reports results in a compact format:

```
README.md
  :3 ✔ echo 'Hello, World!';  [1/5]  0.02s
  :7 ✔ echo 2 + 3;            [2/5]  0.01s
  :11 ✔ echo date('Y');       [3/5]  0.01s
  :15 ⊘ $config = require...  [4/5]
  :19 ✖ echo $result;         [5/5]  0.02s

----------------------------------------
Blocks: 5  Passed: 3  Failed: 1  Skipped: 1  Duration: 0.12s
```

| Symbol | Meaning |
|--------|---------|
| ✔ | Passed |
| ✖ | Failed |
| ⊘ | Skipped (ignored) |
| ✎ | Updated (`--update` mode) |

