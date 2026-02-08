# Security Considerations

DocTest executes PHP code extracted from markdown files. This page covers security aspects to be aware of.

## Code Execution

DocTest **executes arbitrary PHP code** from markdown files. This is by design — it's a testing tool that validates code examples. However, be aware:

- Only run DocTest on **trusted** markdown files
- Don't run DocTest on user-submitted or untrusted content
- Be cautious with markdown files from external sources

## Process Isolation

Each code block runs in a separate PHP process:

- Blocks cannot access the DocTest process's memory or state
- A compromised block cannot affect other blocks
- Timeout limits prevent infinite loops from hanging CI

## File System Access

Code blocks have full file system access within the subprocess. They can:

- Read and write files
- Access the network
- Execute system commands

The `memory_limit` and `timeout` settings provide some boundaries, but they don't sandbox execution.

## Recommendations

### In CI

- Only test markdown files checked into your repository
- Don't test documentation from external pull requests without review
- Use the `--exclude` option to skip untrusted directories

### In Development

- Review markdown files before running DocTest on them
- Use `--dry-run` to see which blocks would execute
- Use `--filter` to limit execution to specific blocks

### General

- Keep DocTest as a **dev dependency** (`--dev`)
- Don't run DocTest in production environments
- Treat markdown files with PHP code blocks as executable code during review

## Temp Files

DocTest writes generated scripts to the system temp directory (`sys_get_temp_dir()`) with a `doctest_run_` prefix and a random suffix. These files contain the instrumented code and are cleaned up automatically after execution. If DocTest crashes, temp files may remain and can be safely deleted.
