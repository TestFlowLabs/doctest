# Update Mode

The `--update` flag automatically updates outdated assertion values with actual execution output. Inspired by Jest's `--updateSnapshot`.

## Usage

```bash
# Update all stale assertions
vendor/bin/doctest --update

# Update a specific file
vendor/bin/doctest docs/api.md -u

# Update a specific block
vendor/bin/doctest docs/api.md:3 -u
```

## Workflow

A typical update workflow after changing underlying code:

```bash
# 1. Run normally to see what's stale
vendor/bin/doctest

# 2. Review the failures, then update
vendor/bin/doctest --update

# 3. Verify everything passes
vendor/bin/doctest

# 4. Review the diff and commit
git diff docs/
```

## Which Assertions Are Updated?

| Assertion | Updated? | Why |
|-----------|----------|-----|
| `<!-- doctest: value -->` | Yes | Unless it contains wildcards |
| `<!-- doctest-json: {} -->` | Yes | Always |
| `$x = 42; // => 42` | Yes | Always |
| `<!-- doctest-output -->` | Yes | Display block content refreshed |
| `<!-- doctest-contains: -->` | No | Partial match can't be auto-generated |
| `<!-- doctest-matches: -->` | No | Regex can't be auto-generated |
| `<!-- doctest-expect: -->` | No | Expression comparison can't be auto-updated |

Wildcard assertions (`{{any}}`, `{{date}}`, etc.) are never updated because their purpose is to match dynamic values.

## Display Output Directive

The `<!-- doctest-output -->` directive marks a display-only output block that gets refreshed on `--update` but is **never asserted** during normal runs.

### Basic Usage

Place the directive between a PHP code block and a plain fenced code block:

````markdown
```php
echo "Hello, World!";
```
<!-- doctest-output -->
```
Hello, World!
```
````

During normal runs, the display block is completely ignored. During `--update`, its content is replaced with actual output.

### Options

#### `lines=N` — Show first N lines

````markdown
```php
for ($i = 1; $i <= 100; $i++) echo "$i\n";
```
<!-- doctest-output: lines=5 -->
```
1
2
3
4
5
```
````

#### `tail=N` — Show last N lines

````markdown
```php
for ($i = 1; $i <= 100; $i++) echo "$i\n";
```
<!-- doctest-output: tail=3 -->
```
98
99
100
```
````

### When to Use

Use `<!-- doctest-output -->` when you want to:

- Show output in documentation without asserting exact values
- Display output that changes frequently (timestamps, random IDs)
- Keep display blocks fresh automatically with `--update`

Use regular assertions (`<!-- doctest: -->`) when output must match exactly.

## Exit Codes

| Code | Meaning |
|------|---------|
| 0 | All updatable assertions updated, no non-updatable failures |
| 1 | Non-updatable assertion failures remain |
| 3 | No test blocks found |

## Mutual Exclusion

`--update` and `--dry-run` cannot be used together. If both are specified, DocTest exits with an error.
