# Getting Started

DocTest is a PHP documentation testing tool that validates code examples in your markdown files. Write documentation with confidence — if it compiles and runs, it stays correct.

## Why DocTest?

Code examples in documentation rot. APIs change, methods get renamed, return types evolve — but the docs stay frozen. DocTest solves this by:

- **Extracting** PHP code blocks from your markdown files
- **Executing** them in isolated processes
- **Verifying** their output matches expected values

If an example breaks, you'll know immediately.

## Quick Start

### 1. Install

```bash
composer require --dev testflowlabs/doctest
```

### 2. Add an Assertion

Write a PHP code block in any markdown file with an assertion in an HTML comment:

````markdown
```php
echo 'Hello, World!';
```
<!-- doctest: Hello, World! -->
````

### 3. Run

```bash
vendor/bin/doctest
```

DocTest scans `docs/` and `README.md` by default. That's it.

## What Happens

1. DocTest finds all `.md` files in the configured paths
2. It extracts PHP code blocks (fenced with `` ```php ``)
3. Each block is executed in an isolated PHP process
4. Output is compared against the assertion
5. Results are reported to the console

## Next Steps

- [Installation](/guide/installation) — Composer setup and requirements
- [How It Works](/guide/how-it-works) — Execution model and architecture
- [Assertions](/assertions/) — All the ways to verify output
- [CLI Options](/cli/options) — Command-line reference
