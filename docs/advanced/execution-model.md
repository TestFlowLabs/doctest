# Execution Model

DocTest executes each code block in an isolated PHP process. This page explains the execution model in detail.

## Process Isolation

Every code block runs in its own PHP subprocess via `proc_open`. This provides:

- **No side effects** — A fatal error in one block doesn't affect others
- **Memory safety** — Memory leaks are contained to the subprocess
- **Clean state** — Each block starts fresh with no implicit dependencies
- **Timeout protection** — Runaway code is killed after the configured timeout

## Code Generation

Before execution, DocTest transforms each code block into a self-contained PHP script:

### Normal Blocks

```php
<?php
$__doctest_results = [];
$__doctest_segment = 0;

ob_start();
echo 'Hello, World!';
$__doctest_output = ob_get_clean();
$__doctest_results[] = [
    'type' => 'output',
    'expected' => 'Hello, World!',
    'actual' => $__doctest_output,
    'line' => 3,
];
$__doctest_segment++;

fwrite(STDERR, json_encode($__doctest_results));
```

Key aspects:

- Output is captured via `ob_start()` / `ob_get_clean()`
- Results are written to `STDERR` as JSON
- Each assertion type generates appropriate instrumentation

### Throws Blocks

```php
<?php
try {
    throw new RuntimeException('Error');
    fwrite(STDERR, json_encode(['thrown' => false]));
} catch (\Throwable $__doctest_e) {
    fwrite(STDERR, json_encode([
        'thrown' => true,
        'class' => get_class($__doctest_e),
        'message' => $__doctest_e->getMessage(),
    ]));
}
```

The code is wrapped in a try/catch to capture exception details.

## Grouped Execution

Blocks with the same `group` attribute execute in a **single process**:

1. All blocks in the group are concatenated into one script
2. `setup` blocks are prepended, `teardown` blocks are appended
3. Assertions are partitioned back to their original blocks for reporting
4. Variables persist across group blocks within the same process

## Process Runner

The process runner manages subprocess execution:

- Uses `proc_open` with pipes for STDIN, STDOUT, and STDERR
- Enforces the configured **timeout** (default: 30 seconds)
- Sets the configured **memory limit** (default: 256M) via `php -d memory_limit=...`
- Captures exit code, STDOUT, and STDERR

## Result Evaluation

After execution, results flow through the comparator:

1. **Output assertions** — Captured output is compared against expected values
2. **Wildcard matching** — If wildcards are present, the expected value is converted to regex
3. **Normalization** — Trailing whitespace is trimmed, line endings are unified
4. **Diff generation** — For failed comparisons, a unified diff is generated

## Temp File Cleanup

Generated scripts are written to `/tmp/doctest/` and cleaned up after execution. Each file has a random name to avoid collisions.
