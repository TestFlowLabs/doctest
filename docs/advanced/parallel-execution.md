# Parallel Execution

DocTest can run code blocks concurrently using multiple worker processes. This can significantly speed up test suites with many independent code blocks.

## Quick Start

Use the `--parallel` (or `-p`) CLI option:

```bash
# Auto-detect CPU cores
doctest --parallel

# Or specify a worker count
doctest --parallel 4
```

Or set it in `doctest.php`:

```php ignore
return [
    'execution' => [
        'parallel' => 4,
    ],
];
```

## How It Works

When `parallel` is greater than 1, DocTest uses a fixed-size worker pool:

1. All code blocks are collected and temp files are generated upfront
2. A pool of N worker processes runs blocks concurrently via `proc_open`
3. Results are collected and evaluated in the original block order
4. Temp files are cleaned up after all workers finish

Each worker runs a single PHP process — the same as sequential mode. The only difference is that multiple blocks execute at the same time.

## What Runs in Parallel

| Block Type | Parallel? | Notes |
|-----------|-----------|-------|
| Normal blocks | Yes | Standard code blocks with assertions |
| `throws` blocks | Yes | Exception-expecting blocks |
| `parse_error` blocks | Yes | Parse error-expecting blocks |
| `ignore` blocks | No | Skipped immediately, no process needed |
| `no_run` blocks | No | Syntax check only, no process needed |
| Grouped blocks | No | Groups run sequentially (shared state) |

## Auto-Detection

When `--parallel` is used without a value, DocTest automatically detects the number of logical CPU cores:

```bash
doctest --parallel
```

This works on Linux (`/proc/cpuinfo`), macOS (`sysctl`), and Windows (`NUMBER_OF_PROCESSORS`).

## Choosing Worker Count

You can also specify the worker count explicitly:

```bash
doctest --parallel 4
```

::: tip
For I/O-bound test suites, you can safely use more workers than CPU cores. For CPU-bound blocks, match the core count.
:::

## Stop on Failure

Stop-on-failure works with parallel execution. When a failure is detected:

1. No new blocks are dispatched to workers
2. Already-running workers finish their current block
3. Results are reported in order up to the failure

```bash
doctest --parallel 4 --stop-on-failure
```

## Deterministic Output

Results are always reported in the original source order, regardless of which worker finishes first. The console output is identical to sequential execution.

## Limitations

- **Grouped blocks** always run sequentially since they share state across blocks
- **Setup/teardown** blocks are applied to each worker process independently
- **Bootstrap profiles** are resolved per-block and included in each worker's temp file

## CLI Reference

```bash
doctest --parallel         # auto-detect CPU cores
doctest --parallel <N>     # or -p <N>
```

| Value | Behavior |
|-------|----------|
| (no value) | Auto-detect CPU core count |
| `1` (default) | Sequential execution |
| `2+` | Parallel execution with N workers |

The `--parallel` CLI option overrides the config file value.
