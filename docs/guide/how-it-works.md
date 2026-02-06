# How It Works

DocTest follows a simple pipeline: **parse**, **generate**, **execute**, **compare**.

## Pipeline Overview

### 1. Parse

DocTest scans markdown files for fenced PHP code blocks (`` ```php ``). For each block, it:

- Extracts the raw PHP code
- Parses attributes from the fence info string (e.g., `ignore`, `throws`, `group="name"`)
- Finds associated assertions (inline comments or HTML comments after the block)
- Strips Shiki-specific markers (`[!code ++]`, `[!code --]`, line highlights)

### 2. Generate

Each code block is transformed into a self-contained PHP script with instrumentation:

- Output-capturing assertions get wrapped in `ob_start()` / `ob_get_clean()`
- Expression assertions (`Expect`) are evaluated and the boolean result is captured
- Result comment assertions (`// => value`) use `var_export` to compare values
- All results are collected and written to `STDERR` as JSON

### 3. Execute

The generated script runs in an **isolated PHP process** via `proc_open`:

- Each block gets its own process (no shared state between blocks)
- Configurable timeout (default: 30 seconds)
- Configurable memory limit (default: 256M)
- Exit code and output are captured

**Exception:** grouped blocks (same `group` attribute) run in a single process to share state.

### 4. Compare

<div v-pre>

The executor evaluates results from the process:

- **Output assertions** compare captured output against expected values
- **Wildcard patterns** (`{{int}}`, `{{date}}`, etc.) are converted to regex
- **JSON assertions** decode and compare structures (key order doesn't matter)
- Output is normalized (trailing whitespace trimmed, line endings unified)

</div>

A diff is generated for failed comparisons.

## Process Isolation

Every code block runs in its own PHP process. This means:

- A fatal error in one block doesn't affect others
- Memory leaks are contained
- Each block starts with a clean state
- No implicit dependencies between examples

## Grouped Execution

Blocks with matching `group` attributes execute in a single process:

1. `setup` blocks run first (in document order)
2. Group blocks run in document order, sharing variables
3. `teardown` blocks run last

This enables examples that build on each other, like database operations.

## Next Steps

- [Assertions](/assertions/) — How output verification works in detail
- [Attributes](/attributes/) — Control execution with fence attributes
- [Execution Model](/advanced/execution-model) — Deep dive into process handling
