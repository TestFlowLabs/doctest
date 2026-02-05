# PHP DocTest Specification

A PHP documentation testing tool that validates code examples in markdown files. Extracts PHP code blocks, executes them in an isolated environment, validates outputs and assertions, and reports failures with clear diffs. Integrates with PHPUnit, Pest, and Laravel.

Inspired by Python's doctest, Rust's rustdoc, Elixir's ExDoc, and Go's testable examples.

## Table of Contents

1. [Overview](#overview)
2. [Requirements](#requirements)
3. [Goals and Non-Goals](#goals-and-non-goals)
4. [Architecture](#architecture)
5. [Markdown Parsing](#markdown-parsing)
6. [Code Block Syntax](#code-block-syntax)
7. [Assertion Syntax](#assertion-syntax)
8. [Execution Model](#execution-model)
9. [Laravel Integration](#laravel-integration)
10. [Output Comparison](#output-comparison)
11. [Error Handling](#error-handling)
12. [Configuration](#configuration)
13. [CLI Interface](#cli-interface)
14. [PHPUnit/Pest Integration](#phpunitpest-integration)
15. [CI/CD Integration](#cicd-integration)
16. [Security Considerations](#security-considerations)
17. [Implementation Phases](#implementation-phases)

---

## Overview

### Problem Statement

Documentation code examples frequently become outdated as the codebase evolves. Manual verification is:
- Time-consuming and error-prone
- Often skipped during refactoring
- Inconsistent across team members

### Solution

A tool that:
1. Extracts PHP code blocks from markdown files
2. Executes them in an isolated environment
3. Validates outputs and assertions
4. Reports failures with clear diffs
5. Integrates with existing test workflows

### Inspiration Sources

| Language | Tool | Key Feature Adopted |
|----------|------|---------------------|
| Python | doctest | Output comparison with `// Output:` comments |
| Rust | rustdoc | Code block attributes (`ignore`, `no_run`) |
| Elixir | ExDoc | Expression assertions |
| Go | testing | `// Output:` comment style |

---

## Requirements

### PHP Version

- **Minimum: PHP 8.3**

### Distribution

- **Type:** Composer package
- **Package:** `tarfinlabs/doctest`
- **Installation:** `composer require tarfinlabs/doctest --dev`

### Dependencies

| Package | Purpose | Required |
|---------|---------|----------|
| `league/commonmark` | Markdown AST parsing | Yes |
| `orchestra/testbench` | Laravel environment bootstrap | No (Laravel integration only) |

### Standalone vs Laravel

The package works in two modes:

| Mode | CLI Command | Requirements |
|------|-------------|--------------|
| Standalone | `vendor/bin/doctest` | PHP 8.3, league/commonmark |
| Laravel | `php artisan doctest` | + orchestra/testbench, Laravel 11+ |

Laravel mode is activated automatically when a Laravel application is detected (via `bootstrap/app.php`).

---

## Goals and Non-Goals

### Goals

1. **Verify documentation accuracy** - Ensure code examples produce expected results
2. **Low friction adoption** - Work with existing markdown without major rewrites
3. **Laravel-first design** - Native support for Laravel testing patterns
4. **Clear failure reporting** - Show exactly what failed and why
5. **Flexible execution** - Support various testing scenarios (output, assertions, exceptions)
6. **CI/CD ready** - Exit codes, machine-readable output, parallel execution

### Non-Goals

1. **Full sandbox isolation** - Not a security boundary (use containers for untrusted code)
2. **IDE integration** - Focus on CLI and CI; IDE plugins are out of scope
3. **Multi-language support** - PHP only (other language blocks in markdown are ignored)
4. **Documentation generation** - This is a tester, not a generator

---

## Architecture

### High-Level Components

```
┌─────────────────────────────────────────────────────────────────┐
│                        DocTest Runner                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐      │
│  │   Markdown   │    │    Code      │    │   Assertion  │      │
│  │   Parser     │───▶│   Extractor  │───▶│   Parser     │      │
│  └──────────────┘    └──────────────┘    └──────────────┘      │
│         │                   │                   │               │
│         ▼                   ▼                   ▼               │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐      │
│  │   AST Node   │    │  CodeBlock   │    │  Assertion   │      │
│  │   Tree       │    │  Collection  │    │  Collection  │      │
│  └──────────────┘    └──────────────┘    └──────────────┘      │
│                                                 │               │
│                                                 ▼               │
│                            ┌──────────────────────────────┐    │
│                            │      Test Executor           │    │
│                            │  ┌────────────────────────┐  │    │
│                            │  │  Laravel Testbench     │  │    │
│                            │  │  Bootstrap (optional)  │  │    │
│                            │  └────────────────────────┘  │    │
│                            └──────────────────────────────┘    │
│                                          │                     │
│                                          ▼                     │
│                            ┌──────────────────────────────┐    │
│                            │      Result Reporter         │    │
│                            │  - Console (colored diff)    │    │
│                            │  - JUnit XML                 │    │
│                            │  - JSON                      │    │
│                            └──────────────────────────────┘    │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Directory Structure

```
src/
├── DocTest.php                  # Main entry point / facade
├── Parser/
│   ├── MarkdownParser.php       # Markdown AST parsing
│   ├── CodeBlockExtractor.php   # Extract code blocks with metadata
│   └── AttributeParser.php      # Parse code block attributes
├── CodeBlock/
│   ├── CodeBlock.php            # Represents a single code block
│   ├── CodeBlockCollection.php  # Collection of blocks from a file
│   └── Attributes.php           # Block attributes value object
├── Assertion/
│   ├── AssertionParser.php      # Parse assertion comments
│   ├── OutputAssertion.php      # // Output: style assertions
│   └── ExpectAssertion.php      # // Expect: style assertions
├── Executor/
│   ├── TestExecutor.php         # Execute code blocks
│   ├── ProcessExecutor.php      # Standalone process execution
│   ├── LaravelExecutor.php      # Laravel-aware execution
│   └── OutputCapture.php        # Capture stdout/stderr
├── Comparison/
│   ├── OutputComparator.php     # Compare expected vs actual
│   ├── Normalizer.php           # Normalize whitespace, etc.
│   └── DiffGenerator.php        # Generate readable diffs
├── Reporter/
│   ├── ConsoleReporter.php      # Terminal output with colors
│   ├── JUnitReporter.php        # JUnit XML format
│   └── JsonReporter.php         # JSON format for tooling
├── Config/
│   └── DocTestConfig.php        # Configuration management
└── Laravel/
    ├── DocTestServiceProvider.php  # Laravel service provider
    └── DocTestCommand.php          # Artisan command
```

---

## Markdown Parsing

### Parser: `league/commonmark`

- Full CommonMark spec compliance
- AST-based parsing (not regex)
- Extensible for custom attributes
- Well-maintained, Laravel-adjacent ecosystem

### Code Block Detection

```php
$environment = new Environment();
$environment->addExtension(new CommonMarkCoreExtension());

$parser = new MarkdownParser($environment);
$document = $parser->parse($markdown);

foreach ($document->iterator() as $node) {
    if ($node instanceof FencedCode) {
        $language = $node->getInfo();   // "php", "php ignore", etc.
        $code = $node->getLiteral();
        $startLine = $node->getStartLine();
    }
}
```

### Language Detection

Only process blocks explicitly marked as PHP:

```markdown
```php             ← Process
```php ignore      ← Process (with attribute)
```php{1,4}        ← Process (VitePress line highlighting)
```PHP             ← Process (case-insensitive)
```javascript     ← Skip
```                ← Skip (no language)
```

---

## Code Block Syntax

### Basic Code Block

````markdown
```php
$greeting = 'Hello, World!';
echo $greeting;
// Output: Hello, World!
```
````

### PHP Tag Handling

Code blocks are executed **without** `<?php` opening tags. The executor wraps code automatically. If a block contains `<?php`, it is stripped before execution.

```php
// No <?php needed — just write PHP code
$x = 1 + 2;
// Expect: $x === 3
```

### Attributes

Attributes are placed after the language identifier on the code fence:

````markdown
```php ignore
// This code is shown in documentation but not executed
$example = 'display only';
```
````

### Attribute Reference

| Attribute | Behavior |
|-----------|----------|
| *(none)* | Execute and verify all assertions |
| `ignore` | Skip entirely (documentation only) |
| `no_run` | Parse but don't execute (syntax check only) |
| `throws` | Expect any exception to be thrown |
| `throws(ExceptionClass)` | Expect specific exception type (`instanceof` check) |
| `throws(ExceptionClass, "message")` | Expect exception type with message substring |
| `parse_error` | Expect PHP parse/syntax error |
| `setup` | Run before all other blocks in the file |
| `teardown` | Run after all other blocks in the file |
| `group="name"` | Share execution context with same-named blocks |
| `standalone` | Run in a separate PHP process (extra isolation) |

### Attribute Examples

#### `ignore` — Documentation Only

````markdown
```php ignore
// Pseudocode showing internal implementation
// Not meant to be executed
$internalState = new InternalState();
$internalState->doMagic();
```
````

#### `no_run` — Parse Without Executing

````markdown
```php no_run
// Syntax is validated but code doesn't run
// Useful for examples requiring external services
$api->connect('production.example.com');
$api->deploy();
```
````

#### `throws` — Exception Testing

````markdown
```php throws
// Expects any exception
throw new \RuntimeException('Something went wrong');
```
````

````markdown
```php throws(InvalidArgumentException)
// Expects a specific exception type
$machine->send(['type' => 'INVALID_EVENT']);
```
````

````markdown
```php throws(InvalidArgumentException, "Bad input")
// Expects exception type AND message substring
validate($invalidData);
```
````

#### `parse_error` — Expected Syntax Errors

````markdown
```php parse_error
// This code intentionally has a syntax error
$x = ;; // demonstrates what NOT to do
```
````

#### `setup` / `teardown` — File-Level Lifecycle

````markdown
```php setup
// Runs once before all blocks in this file
$sharedConfig = ['env' => 'testing'];
```
````

````markdown
```php teardown
// Runs once after all blocks in this file
cleanupTestArtifacts();
```
````

#### `group` — Shared Execution Context

Blocks with the same group name share variable scope and state. They execute in document order.

````markdown
```php group="order-flow"
$machine = OrderMachine::create();
echo $machine->state->value;
// Output: pending
```

Some documentation text between blocks...

```php group="order-flow"
$machine->send(['type' => 'PAY']);
echo $machine->state->value;
// Output: paid
```

```php group="order-flow"
$machine->send(['type' => 'SHIP']);
echo $machine->state->value;
// Output: shipped
```
````

#### `standalone` — Process Isolation

For blocks that need a completely clean PHP process (e.g., testing global state, `define()`, `ini_set()`):

````markdown
```php standalone
define('MY_CONSTANT', 42);
echo MY_CONSTANT;
// Output: 42
```
````

### Blocks Without Assertions

A code block with no assertion comments (`// Output:`, `// Expect:`) is still executed. It passes if it completes without throwing an unhandled exception. This serves as a "smoke test" — verifying the example doesn't crash.

````markdown
```php
// No assertions — passes if it doesn't throw
$machine = TrafficLightMachine::create();
$machine->send(['type' => 'NEXT']);
```
````

### VitePress Compatibility

Line highlighting metadata is stripped before attribute parsing:

````markdown
```php{1,4-6}
// Lines 1 and 4-6 are highlighted in rendered docs
// The entire block is executed
$highlighted = true;
```
````

VitePress diff markers are stripped before execution:

````markdown
```php
$before = 'old'; // [!code --]
$after = 'new';  // [!code ++]
// Only $after = 'new'; is executed
```
````

---

## Assertion Syntax

All assertions are written as specially-formatted PHP comments within code blocks.

### Output Assertions (`// Output:`)

Matches captured `stdout` (from `echo`, `print`, `print_r`, `var_dump`, etc.):

```php
echo "Hello, World!";
// Output: Hello, World!
```

Multi-line output uses `//` prefix per line:

```php
print_r(['a' => 1, 'b' => 2]);
// Output:
// Array
// (
//     [a] => 1
//     [b] => 2
// )
```

### Expression Assertions (`// Expect:`)

Evaluates a PHP expression in the block's variable scope. The expression **must** return a truthy value.

```php
$sum = 1 + 2;
// Expect: $sum === 3
```

```php
$items = [1, 2, 3];
// Expect: count($items) === 3
// Expect: $items[0] === 1
```

```php
$machine = TrafficLightMachine::create();
// Expect: $machine instanceof Machine
// Expect: $machine->state !== null
```

**How it works:** The expression after `// Expect:` is evaluated via `eval('return (' . $expression . ');')` with access to all variables defined in the block. If the result is falsy, the assertion fails.

**Important:** Always use explicit expressions. Avoid bare values:

```php
// GOOD — explicit comparison
$result = $machine->state->matches('pending');
// Expect: $result === true

// GOOD — inline expression
// Expect: $machine->state->matches('pending') === true

// BAD — ambiguous bare value (what does "true" refer to?)
$machine->state->matches('pending');
// Expect: true
```

### Subset Output Assertions

#### Contains Match (`// OutputContains:`)

```php
echo "The result is: 42 (calculated)";
// OutputContains: 42
```

#### Regex Match (`// OutputMatches:`)

```php
echo "Order #" . rand(1000, 9999);
// OutputMatches: /Order #\d{4}/
```

#### JSON Match (`// OutputJson:`)

```php
echo json_encode(['status' => 'ok', 'data' => ['id' => 1]]);
// OutputJson: {"status": "ok", "data": {"id": 1}}
```

JSON comparison ignores key ordering and whitespace formatting. Wildcard placeholders can be used for dynamic values.

### Wildcard Placeholders

For dynamic values in output assertions:

```php
echo "Generated ID: " . uniqid();
// Output: Generated ID: {{any}}
```

```php
echo json_encode(['id' => 123, 'created_at' => now()]);
// Output: {"id":123,"created_at":"{{datetime}}"}
```

#### Built-in Wildcards

| Wildcard | Matches |
|----------|---------|
| `{{any}}` | Any non-empty string (non-greedy) |
| `{{int}}` | Integer (`-?\d+`) |
| `{{float}}` | Float (`-?\d+\.?\d*`) |
| `{{uuid}}` | UUID v4 format |
| `{{datetime}}` | ISO 8601 datetime |
| `{{date}}` | Date (`Y-m-d`) |
| `{{time}}` | Time (`H:i:s`) |
| `{{...}}` | Any amount of text including newlines |

### Assertion Placement Rules

Assertions must immediately follow the statement that produces the output:

```php
// CORRECT — assertion right after the producing statement
echo "First";
// Output: First

echo "Second";
// Output: Second
```

```php
// INCORRECT — code between statement and assertion
echo "First";
$intermediate = 42;    // ← intervening code
// Output: First       // ← assertion cannot match; output buffer already advanced
```

The reason: output is captured sequentially. Each `// Output:` assertion is matched against the stdout produced by statements since the previous assertion (or start of block).

---

## Execution Model

### How Code Execution Works

1. Code is extracted from the markdown block (without `<?php` tags)
2. VitePress markers are stripped
3. Assertion comments are parsed and extracted
4. Code is executed via `eval()` (default) or separate process (`standalone`)
5. `stdout` is captured via `ob_start()` / `ob_get_clean()`
6. Captured output is compared against `// Output:` assertions
7. `// Expect:` expressions are evaluated in the block's variable scope
8. Exceptions are matched against `throws` attribute (if present)

### Execution Contexts

#### Isolated (Default)

Each code block runs in a fresh PHP context. No state leaks between blocks:

```php
// Block 1
$x = 1;
```

```php
// Block 2 — $x is NOT available here
// This would cause: "Undefined variable $x"
```

#### Grouped

Blocks with the same `group` attribute share execution context. They run in document order, preserving variables, imports, and (in Laravel mode) database state:

````markdown
```php group="counter"
$counter = 0;
```

```php group="counter"
$counter++;
// Expect: $counter === 1
```

```php group="counter"
$counter++;
// Expect: $counter === 2
```
````

Groups are independent — blocks in different groups don't share state.

#### Standalone

Blocks with `standalone` run in a completely separate PHP process. Use for code that modifies global state (`define()`, `ini_set()`, static properties):

````markdown
```php standalone
ini_set('precision', 14);
echo ini_get('precision');
// Output: 14
```
````

### Execution Order

1. **`setup` blocks** — once per file, before everything else
2. **Regular blocks** — in document order, each isolated
3. **Grouped blocks** — in document order within each group, sharing state
4. **`teardown` blocks** — once per file, after everything else

### Imports and Namespaces

Imports can be configured globally (in `doctest.php`) or declared explicitly per block:

**Global (config-based):**
```php
// doctest.php: 'imports' => ['App\\Machines\\*']
// The executor auto-generates `use` statements via class map resolution.
// Wildcard `*` resolves all classes in the namespace using Composer's autoloader.

// So this works without explicit `use` statements:
$machine = TrafficLightMachine::create();
```

**Explicit (in-block):**
```php
use App\Machines\TrafficLightMachine;

$machine = TrafficLightMachine::create();
```

### Parallel Execution

When `execution.parallel` is configured with N > 1 workers:

- Parallelism is at the **file level** — each file runs in its own worker
- Blocks within a file always execute sequentially (preserving group semantics)
- Each worker has its own PHP process and (in Laravel mode) its own database
- `standalone` blocks always get their own process regardless of parallelism

---

## Laravel Integration

When Laravel is detected, the executor bootstraps a full application environment using Orchestra Testbench.

### Bootstrap

```php
class DocTestExecutor extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        // From doctest.php 'providers' config
        return config('doctest.providers', []);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(config('doctest.database.migrations_path'));
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);
    }
}
```

### Database State

Each isolated block starts with a fresh database (migrations re-run). Grouped blocks share database state within the group:

```php
// Isolated block — fresh database
$machine = OrderMachine::create();
// Creates a row in machine_events
```

```php
// Another isolated block — fresh database, previous data is gone
$count = MachineEvent::count();
// Expect: $count === 0
```

````markdown
```php group="persistence"
$machine = OrderMachine::create();
$rootEventId = $machine->state->history->first()->root_event_id;
```

```php group="persistence"
// Same database — previous data is available
$restored = OrderMachine::create(state: $rootEventId);
// Expect: $restored->state->value === $machine->state->value
```
````

### Service Container

Full Laravel service container is available:

```php
$machine = app(OrderMachine::class);
// Expect: $machine instanceof OrderMachine
```

### Configuration

Laravel config is accessible:

```php
echo config('machine.tables.events');
// Output: machine_events
```

---

## Output Comparison

### Normalization Rules

Before comparing expected vs actual output:

1. **Trailing whitespace** — removed from each line
2. **Trailing newlines** — normalized to single newline
3. **Windows line endings** — `\r\n` converted to `\n`
4. **Leading/trailing blank lines** — removed

### Diff Generation

When output doesn't match, show a clear diff:

```
FAILED: docs/getting-started.md:42

Expected:
  trafficLight.green

Actual:
  trafficLight.red

Diff:
  - trafficLight.green
  + trafficLight.red
```

Multi-line diff:

```
FAILED: docs/patterns/order-processing.md:87

Expected:
  Array
  (
      [status] => pending
      [total] => 99.99
  )

Actual:
  Array
  (
      [status] => pending
      [total] => 100.00
  )

Diff:
    Array
    (
        [status] => pending
  -     [total] => 99.99
  +     [total] => 100.00
    )
```

---

## Error Handling

### Error Types

| Error Type | Handling |
|------------|----------|
| Parse error | Report with line number; suggest `parse_error` attribute if intentional |
| Runtime exception | Check against `throws` attribute; fail if unexpected |
| Assertion failure | Show expected vs actual with diff |
| Timeout | Kill execution, report timeout (configurable limit) |
| Memory limit | Report OOM, suggest optimization or `ignore` |

### Error Messages

Clear, actionable messages with source location and hints:

```
ERROR: docs/advanced/parallel-states.md:156

  Code block failed to execute:

  ParseError: syntax error, unexpected '}' on line 3

  Code:
  1 | $machine = ParallelMachine::create();
  2 | $machine->send(['type' => 'START']
  3 | }
    |  ^ unexpected '}'

  Hint: If this is intentional, add the `parse_error` attribute:
  ```php parse_error
```

### Exception Matching

When the `throws` attribute is present:

```php
// throws — any Throwable passes
throw new RuntimeException('test');
// ✓ passes (any exception accepted)
```

```php
// throws(InvalidArgumentException) — instanceof check
throw new InvalidArgumentException('test');
// ✓ passes (correct type)

throw new RuntimeException('test');
// ✗ fails (wrong type)
```

```php
// throws(InvalidArgumentException, "Bad input") — type + message substring
throw new InvalidArgumentException('Bad input provided');
// ✓ passes (correct type, message contains "Bad input")

throw new InvalidArgumentException('Wrong value');
// ✗ fails (correct type, but message doesn't contain "Bad input")
```

---

## Configuration

### Configuration File

Located at project root as `doctest.php` (standalone) or published to `config/doctest.php` (Laravel):

```php
<?php

return [
    // Directories and files containing markdown to test
    'paths' => [
        'docs',
        'README.md',
    ],

    // Glob patterns to exclude
    'exclude' => [
        'docs/archive/*',
        'docs/**/*draft*.md',
    ],

    // Namespaces automatically imported in all code blocks
    // Wildcard '*' resolves via Composer's class map
    'imports' => [
        'App\\Machines\\*',
        'Tarfinlabs\\EventMachine\\Actor\\Machine',
    ],

    // File to require before running any tests
    'bootstrap' => null,

    // Laravel service providers to register (Laravel mode only)
    'providers' => [],

    'execution' => [
        'timeout' => 30,           // Seconds per block
        'memory_limit' => '256M',  // Memory limit per block
        'parallel' => 1,           // Number of parallel workers (file-level)
        'stop_on_failure' => false, // Stop at first failure
    ],

    'output' => [
        'normalize_whitespace' => true,
        'trim_trailing' => true,
        'verbose_failures' => true,
    ],

    // Database settings (Laravel mode only)
    'database' => [
        'connection' => 'testing',
        'driver' => 'sqlite',
        'database' => ':memory:',
        'refresh_between_blocks' => true,
        'migrations' => true,
    ],

    'reporters' => [
        'console' => true,
        'junit' => null,  // Set file path to enable: 'build/doctest.xml'
        'json' => null,   // Set file path to enable: 'build/doctest.json'
    ],
];
```

### Environment Overrides

```bash
# .env or .env.testing
DOCTEST_DB_CONNECTION=sqlite
DOCTEST_TIMEOUT=60
DOCTEST_PARALLEL=4
```

---

## CLI Interface

### Commands

```bash
# Run all documentation tests
vendor/bin/doctest                          # Standalone
php artisan doctest                          # Laravel

# Run specific file or directory
vendor/bin/doctest docs/getting-started.md
vendor/bin/doctest docs/patterns/

# Filter by pattern (matches file paths and block descriptions)
vendor/bin/doctest --filter="parallel"

# Dry run — parse and report without executing
vendor/bin/doctest --dry-run

# Audit — list all code that would be executed (security review)
vendor/bin/doctest --audit
```

### Options

| Option | Description |
|--------|-------------|
| `--filter=PATTERN` | Only run blocks matching pattern |
| `--exclude=PATTERN` | Skip blocks matching pattern |
| `--dry-run` | Parse only, don't execute |
| `--audit` | List all executable code without running it |
| `--stop-on-failure` | Stop at first failure |
| `--parallel[=N]` | Run in parallel (N workers, file-level) |
| `--no-progress` | Hide progress bar |
| `--junit=FILE` | Output JUnit XML to file |
| `--json=FILE` | Output JSON to file |
| `-v / -vv / -vvv` | Verbosity levels |

### Exit Codes

| Code | Meaning |
|------|---------|
| 0 | All tests passed |
| 1 | Some tests failed |
| 2 | Configuration error |
| 3 | No tests found |

### Progress Output

```
DocTest v1.0.0

docs/getting-started.md
  ✓ Machine definition (line 42)
  ✓ Sending events (line 67)
  ✓ State matching (line 89)

docs/advanced/parallel-states.md
  ✓ Basic parallel definition (line 23)
  ✗ Nested parallel states (line 156)
    Expected: trafficLight.green
    Actual:   trafficLight.red
  ○ Complex example (line 234) [ignore]

docs/patterns/order-processing.md
  ✓ Order creation (line 12)
  ✓ Payment flow (line 45)
  ✓ Shipping flow (line 78)

─────────────────────────────────────────
Files:     3
Blocks:    9 (1 ignored)
Passed:    7
Failed:    1
Duration:  2.34s
```

### Audit Output

```bash
$ vendor/bin/doctest --audit

docs/guide.md:42    $machine = TrafficLightMachine::create();
docs/guide.md:67    file_put_contents('output.txt', $data);  ⚠️ FILESYSTEM
docs/api.md:12      Http::get('https://api.example.com');    ⚠️ NETWORK
```

---

## PHPUnit/Pest Integration

### Pest Plugin

```php
// tests/Pest.php
uses(\Tarfinlabs\DocTest\Testing\DocTestCase::class)->in('DocTest');
```

```php
// tests/DocTest/DocumentationTest.php
doctest('docs/getting-started.md');
doctest('docs/advanced/parallel-states.md');
doctest('docs/patterns/*.md');
```

### PHPUnit Data Provider

```php
use PHPUnit\Framework\TestCase;
use Tarfinlabs\DocTest\DocTest;

class DocumentationTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('documentationProvider')]
    public function test_documentation_examples(string $file, int $line, string $code): void
    {
        $result = DocTest::execute($code);

        $this->assertTrue(
            $result->passed,
            "Documentation example at {$file}:{$line} failed:\n{$result->error}"
        );
    }

    public static function documentationProvider(): iterable
    {
        $docTest = new DocTest();

        foreach ($docTest->findAllBlocks('docs/') as $block) {
            yield "{$block->file}:{$block->line}" => [
                $block->file,
                $block->line,
                $block->code,
            ];
        }
    }
}
```

### Test Trait

```php
use Tarfinlabs\DocTest\Testing\TestsDocumentation;

class MyTest extends TestCase
{
    use TestsDocumentation;

    public function test_readme_examples(): void
    {
        $this->assertDocumentationPasses('README.md');
    }

    public function test_all_docs(): void
    {
        $this->assertDocumentationPasses('docs/');
    }

    public function test_specific_block(): void
    {
        $this->assertDocBlockPasses('docs/guide.md', line: 42);
    }
}
```

---

## CI/CD Integration

### GitHub Actions

```yaml
name: Documentation Tests

on:
  push:
    paths: ['docs/**', 'src/**']
  pull_request:
    paths: ['docs/**', 'src/**']

jobs:
  doctest:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, sqlite3

      - name: Install Dependencies
        run: composer install --no-progress

      - name: Run Documentation Tests
        run: vendor/bin/doctest --junit=build/doctest.xml

      - name: Upload Results
        uses: actions/upload-artifact@v4
        if: failure()
        with:
          name: doctest-results
          path: build/doctest.xml
```

### GitLab CI

```yaml
doctest:
  stage: test
  script:
    - composer install --no-progress
    - vendor/bin/doctest --junit=build/doctest.xml
  artifacts:
    when: always
    reports:
      junit: build/doctest.xml
  rules:
    - changes:
        - docs/**/*
        - src/**/*
```

### Pre-commit Hook

```bash
#!/bin/bash
# .git/hooks/pre-commit

STAGED_DOCS=$(git diff --cached --name-only --diff-filter=ACM | grep -E '\.md$')

if [ -n "$STAGED_DOCS" ]; then
    echo "Running doctest on staged documentation..."
    vendor/bin/doctest $STAGED_DOCS --stop-on-failure

    if [ $? -ne 0 ]; then
        echo "Documentation tests failed. Please fix before committing."
        exit 1
    fi
fi
```

---

## Security Considerations

**This tool executes arbitrary PHP code.** It is NOT a security sandbox.

### Mitigations

1. Only run on trusted documentation (your own repo)
2. Never run on user-submitted content
3. Use containers/VMs for untrusted code
4. Execution time and memory are enforced per block

### Safe Defaults

- `set_time_limit()` is enforced — blocks cannot override timeout
- `ini_set('memory_limit')` is enforced — blocks cannot override memory limit
- Dangerous function calls are flagged in `--audit` mode

### Audit Mode

Use `--audit` to review all code before execution:

```bash
vendor/bin/doctest --audit
```

Flags operations involving: filesystem access, network requests, process execution, and system configuration changes.

---

## Implementation Phases

### Phase 1: Core (MVP)

**Goal:** Run simple code blocks with output assertions

- [ ] Markdown parser with code block extraction (league/commonmark)
- [ ] Attribute parsing (`ignore`, `no_run`, `throws`, `parse_error`)
- [ ] `// Output:` assertion parsing and matching
- [ ] Code execution via `eval()` with output capture
- [ ] Console reporter with pass/fail and line numbers
- [ ] Standalone CLI (`vendor/bin/doctest`)

**Milestone:** `echo "hello"; // Output: hello` works end-to-end.

### Phase 2: Assertions and Attributes

**Goal:** Full assertion and attribute support

- [ ] `// Expect:` expression assertions
- [ ] `// OutputContains:`, `// OutputMatches:`, `// OutputJson:`
- [ ] Wildcard placeholders (`{{any}}`, `{{int}}`, etc.)
- [ ] Exception testing via `throws` attribute
- [ ] `group` attribute with shared execution context
- [ ] `setup` / `teardown` lifecycle blocks
- [ ] `standalone` process isolation
- [ ] Configuration file (`doctest.php`)

**Milestone:** grouped blocks, exception tests, and wildcard matching work.

### Phase 3: Laravel Integration

**Goal:** Full Laravel environment support

- [ ] Orchestra Testbench bootstrap
- [ ] Database setup/teardown per block and per group
- [ ] Service provider registration
- [ ] Artisan command (`php artisan doctest`)
- [ ] Auto-import via Composer class map

**Milestone:** code using Eloquent, config, and service container works.

### Phase 4: CI/CD and Integrations

**Goal:** Seamless integration with existing workflows

- [ ] JUnit XML reporter
- [ ] JSON reporter
- [ ] PHPUnit data provider integration
- [ ] Pest plugin
- [ ] `TestsDocumentation` trait
- [ ] Parallel execution (file-level workers)

**Milestone:** `composer test` includes documentation tests; CI produces JUnit reports.

### Phase 5: Polish

**Goal:** Developer experience

- [ ] Colored diff output
- [ ] Progress bar
- [ ] Watch mode (re-run on file change)
- [ ] IDE-compatible error format (clickable `file:line` paths)
- [ ] VitePress marker stripping
- [ ] `--audit` security review mode

**Milestone:** pleasant CLI experience, comprehensive docs.

---

## Appendix A: Full Example

### Documentation File

````markdown
# Traffic Light Machine

A simple state machine that models a traffic light.

## Definition

```php
use Tarfinlabs\EventMachine\Definition\MachineDefinition;

$definition = MachineDefinition::define([
    'id' => 'trafficLight',
    'initial' => 'green',
    'states' => [
        'green'  => ['on' => ['NEXT' => 'yellow']],
        'yellow' => ['on' => ['NEXT' => 'red']],
        'red'    => ['on' => ['NEXT' => 'green']],
    ],
]);
// Expect: $definition->id === 'trafficLight'
```

## Usage

```php group="traffic-light-usage"
use App\Machines\TrafficLightMachine;

$machine = TrafficLightMachine::create();
echo $machine->state->value[0];
// Output: trafficLight.green
```

```php group="traffic-light-usage"
$machine->send(['type' => 'NEXT']);
echo $machine->state->value[0];
// Output: trafficLight.yellow
```

## Error Handling

```php throws(InvalidStateException)
$machine = TrafficLightMachine::create();
$machine->send(['type' => 'INVALID_EVENT']);
```

## State Matching

```php
$machine = TrafficLightMachine::create();

$matches = $machine->state->matches('trafficLight.green');
// Expect: $matches === true

$matchesShort = $machine->state->matches('green');
// Expect: $matchesShort === true
```

## Internals (Not Tested)

```php ignore
// Pseudocode — not meant to be executed
$internalState = new InternalState();
$internalState->doMagic();
```
````

### Test Run Output

```
$ vendor/bin/doctest docs/traffic-light.md

DocTest v1.0.0

docs/traffic-light.md
  ✓ Definition (line 12)
  ✓ Usage - initial state (line 32)
  ✓ Usage - after NEXT (line 38)
  ✓ Error Handling (line 45)
  ✓ State Matching (line 52)
  ○ Internals (line 72) [ignore]

─────────────────────────────────────────
Files:     1
Blocks:    6 (1 ignored)
Passed:    5
Failed:    0
Duration:  0.89s

All documentation tests passed! ✓
```

---

## Appendix B: Comparison with Existing Tools

| Feature | DocTest | PHPUnit | Pest | Python doctest |
|---------|---------|---------|------|----------------|
| Markdown extraction | ✓ | ✗ | ✗ | ✓ |
| Laravel integration | ✓ | Manual | Manual | N/A |
| Output assertions | ✓ | Manual | Manual | ✓ |
| Code block attributes | ✓ | N/A | N/A | Limited |
| Wildcard matching | ✓ | ✗ | ✗ | ✓ |
| Exception testing | ✓ | ✓ | ✓ | ✓ |
| Parallel execution | ✓ | ✓ | ✓ | ✗ |
| JUnit output | ✓ | ✓ | ✓ | ✗ |
| VitePress support | ✓ | N/A | N/A | N/A |

---

## Appendix C: Glossary

| Term | Definition |
|------|------------|
| Code Block | A fenced code block in markdown (`` ``` ``) marked as PHP |
| Assertion | A specially-formatted comment specifying expected output or behavior |
| Attribute | A modifier on a code block's fence line (e.g., `ignore`, `throws`) |
| Group | Named set of blocks that share execution context |
| Bootstrap | Setup code/file run before any tests execute |
| Wildcard | A placeholder in expected output matching dynamic values |
| Standalone | A block executed in its own separate PHP process |
| Smoke Test | A block without assertions that passes if it doesn't throw |
