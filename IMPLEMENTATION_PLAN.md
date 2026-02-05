# DocTest Implementation Plan

Detailed implementation plan for `testflowlabs/doctest` — a PHP documentation testing tool.

This plan covers Phase 1 (Core MVP) and Phase 2 (Assertions & Attributes) from the spec. Later phases will be planned separately once the foundation is solid.

---

## Phase 0: Project Scaffolding

### 0.1 — composer.json

```json
{
    "name": "testflowlabs/doctest",
    "description": "PHP documentation testing tool — validate code examples in markdown files",
    "type": "library",
    "license": "MIT",
    "minimum-stability": "stable",
    "require": {
        "php": "^8.3",
        "league/commonmark": "^2.0"
    },
    "require-dev": {
        "pestphp/pest": "^3.0",
        "laravel/pint": "^1.0",
        "phpstan/phpstan": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "TestFlowLabs\\DocTest\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "TestFlowLabs\\DocTest\\Tests\\": "tests/"
        }
    },
    "bin": [
        "bin/doctest"
    ],
    "config": {
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true
        }
    }
}
```

### 0.2 — Directory Structure

```
doctest/
├── bin/
│   └── doctest                  # CLI entry point (PHP script)
├── src/
│   ├── DocTest.php              # Main facade / entry point
│   ├── Parser/
│   │   ├── MarkdownParser.php
│   │   ├── CodeBlockExtractor.php
│   │   └── AttributeParser.php
│   ├── CodeBlock/
│   │   ├── CodeBlock.php
│   │   ├── CodeBlockCollection.php
│   │   └── Attribute.php
│   ├── Assertion/
│   │   ├── AssertionParser.php
│   │   ├── Assertion.php
│   │   ├── OutputAssertion.php
│   │   └── ExpectAssertion.php
│   ├── Executor/
│   │   ├── Executor.php
│   │   ├── CodeGenerator.php
│   │   ├── ProcessRunner.php
│   │   └── ExecutionResult.php
│   ├── Comparison/
│   │   ├── OutputComparator.php
│   │   ├── Normalizer.php
│   │   └── DiffGenerator.php
│   ├── Reporter/
│   │   └── ConsoleReporter.php
│   └── Config/
│       └── DocTestConfig.php
├── tests/
│   ├── Pest.php
│   ├── Unit/
│   │   ├── Parser/
│   │   ├── Assertion/
│   │   ├── Executor/
│   │   ├── Comparison/
│   │   └── Config/
│   ├── Integration/
│   │   └── EndToEndTest.php
│   └── Fixtures/
│       └── *.md
├── composer.json
├── phpstan.neon
├── pint.json
├── DOCTEST_SPEC.md
└── .gitignore
```

### 0.3 — Development Tool Configuration

**phpstan.neon:**
```yaml
parameters:
    level: max
    paths:
        - src
    tmpDir: build/phpstan
```

**pint.json:**
```json
{
    "preset": "per",
    "rules": {
        "concat_space": {
            "spacing": "none"
        }
    }
}
```

**tests/Pest.php:**
```php
<?php

pest()->project()->github('TestFlowLabs/doctest');
```

### 0.4 — CLI Entry Point

**bin/doctest:**
```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

// Find Composer autoloader
$autoloadPaths = [
    __DIR__.'/../vendor/autoload.php',       // When running from this package
    __DIR__.'/../../../autoload.php',         // When installed as a dependency
];

foreach ($autoloadPaths as $autoload) {
    if (file_exists($autoload)) {
        require $autoload;
        break;
    }
}

// Bootstrap and run
$config = \TestFlowLabs\DocTest\Config\DocTestConfig::load();
$docTest = new \TestFlowLabs\DocTest\DocTest($config);

exit($docTest->run($argv));
```

### 0.5 — Deliverables

- [ ] Create `composer.json`
- [ ] Create all directories
- [ ] Create `bin/doctest` entry point
- [ ] Create `phpstan.neon`
- [ ] Create `pint.json`
- [ ] Create `tests/Pest.php`
- [ ] Run `composer install`
- [ ] Verify `vendor/bin/pest` works (zero tests)
- [ ] Verify `vendor/bin/phpstan` works (zero files)
- [ ] Verify `vendor/bin/pint` works

---

## Phase 1: Core MVP

Goal: `echo "hello"; // Output: hello` works end-to-end.

The implementation follows the data flow: **Parse → Extract → Assert → Execute → Compare → Report**.

### Step 1.1 — Value Objects

Build the data structures first. Everything else depends on these.

#### `src/CodeBlock/Attribute.php`

Enum representing all possible code block attributes.

```php
enum Attribute: string
{
    case Ignore = 'ignore';
    case NoRun = 'no_run';
    case Throws = 'throws';
    case ParseError = 'parse_error';
    case Setup = 'setup';
    case Teardown = 'teardown';
}
```

Design note: `throws(Class, "msg")` and `group="name"` carry parameters. These need a separate `Attributes` value object that holds the enum + parameters:

```php
final readonly class Attributes
{
    public function __construct(
        public ?Attribute $attribute = null,
        public ?string $throwsClass = null,
        public ?string $throwsMessage = null,
        public ?string $group = null,
    ) {}

    public function isIgnore(): bool { ... }
    public function isThrows(): bool { ... }
    public function hasGroup(): bool { ... }
    // etc.
}
```

#### `src/CodeBlock/CodeBlock.php`

Represents a single extracted code block.

```php
final readonly class CodeBlock
{
    public function __construct(
        public string $file,          // Source markdown file path
        public int $line,             // Line number in the markdown file
        public string $rawCode,       // Original code (with assertion comments)
        public string $executableCode,// Code with assertions stripped
        public Attributes $attributes,
        public array $assertions,     // Assertion[] parsed from comments
    ) {}
}
```

#### `src/CodeBlock/CodeBlockCollection.php`

Collection of code blocks from a single markdown file. Provides methods to group blocks, get setup/teardown, iterate, etc.

#### `src/Assertion/Assertion.php`

Interface for all assertion types:

```php
interface Assertion
{
    public function type(): string;    // 'output', 'expect', etc.
    public function line(): int;       // Line number in the code block
}
```

#### `src/Assertion/OutputAssertion.php`

```php
final readonly class OutputAssertion implements Assertion
{
    public function __construct(
        public string $expected,  // Expected output text
        public int $line,         // Line in code block
    ) {}
}
```

#### `src/Assertion/ExpectAssertion.php`

```php
final readonly class ExpectAssertion implements Assertion
{
    public function __construct(
        public string $expression,  // PHP expression that must be truthy
        public int $line,
    ) {}
}
```

#### `src/Executor/ExecutionResult.php`

```php
final readonly class ExecutionResult
{
    public function __construct(
        public bool $passed,
        public CodeBlock $codeBlock,
        public ?string $actualOutput = null,
        public ?string $expectedOutput = null,
        public ?string $diff = null,
        public ?string $error = null,
        public ?Throwable $exception = null,
        public float $duration = 0.0,
    ) {}
}
```

#### Tests

```
tests/Unit/CodeBlock/AttributeTest.php    — Attribute enum values
tests/Unit/CodeBlock/AttributesTest.php   — Attributes value object
tests/Unit/CodeBlock/CodeBlockTest.php    — CodeBlock creation
tests/Unit/Assertion/OutputAssertionTest.php
tests/Unit/Assertion/ExpectAssertionTest.php
tests/Unit/Executor/ExecutionResultTest.php
```

#### Deliverables

- [ ] Create `Attribute` enum
- [ ] Create `Attributes` value object
- [ ] Create `CodeBlock` value object
- [ ] Create `CodeBlockCollection`
- [ ] Create `Assertion` interface
- [ ] Create `OutputAssertion`
- [ ] Create `ExpectAssertion`
- [ ] Create `ExecutionResult`
- [ ] Write tests for all value objects
- [ ] All tests pass, phpstan clean

---

### Step 1.2 — Markdown Parsing

Extract PHP code blocks from markdown files using `league/commonmark`.

#### `src/Parser/MarkdownParser.php`

Wraps `league/commonmark` to parse markdown into an AST.

```php
final readonly class MarkdownParser
{
    public function parse(string $markdown): Document { ... }
}
```

Internally:
1. Create a `league/commonmark` `Environment` with `CommonMarkCoreExtension`
2. Parse markdown string into a `Document` AST node

#### `src/Parser/CodeBlockExtractor.php`

Walks the AST and extracts PHP code blocks.

```php
final readonly class CodeBlockExtractor
{
    public function __construct(
        private AttributeParser $attributeParser,
        private AssertionParser $assertionParser,
    ) {}

    /** @return CodeBlock[] */
    public function extract(Document $document, string $filePath): array { ... }
}
```

Algorithm:
1. Walk the AST using `$document->iterator()`
2. For each `FencedCode` node:
   a. Get info string (`$node->getInfo()`)
   b. Check if it's PHP (case-insensitive, strip Shiki `{...}` metadata)
   c. Parse attributes from remaining info string
   d. Get code literal (`$node->getLiteral()`)
   e. Strip `<?php` tag if present
   f. Strip Shiki markers (`// [!code --]`, `// [!code ++]`)
   g. Parse assertion comments from code
   h. Generate executable code (assertions stripped)
   i. Create `CodeBlock` value object
3. Return array of `CodeBlock`

#### `src/Parser/AttributeParser.php`

Parses the info string after language identifier.

```php
final readonly class AttributeParser
{
    public function parse(string $infoString): Attributes { ... }
}
```

Parsing rules:
1. Input: `"php ignore"`, `"php throws(InvalidArgumentException, \"Bad input\")"`, `"php group=\"order-flow\""`, `"php{1,4-6}"`, `"PHP"`
2. Strip language identifier (`php`/`PHP`) and Shiki metadata (`{...}`)
3. Remaining string is the attribute specification
4. Parse attribute keyword and optional arguments

Supported patterns:
- `""` → no attribute
- `"ignore"` → `Attribute::Ignore`
- `"no_run"` → `Attribute::NoRun`
- `"setup"` → `Attribute::Setup`
- `"teardown"` → `Attribute::Teardown`
- `"parse_error"` → `Attribute::ParseError`
- `"throws"` → `Attribute::Throws`, no class/message
- `"throws(ClassName)"` → `Attribute::Throws`, class = ClassName
- `"throws(ClassName, \"message\")"` → `Attribute::Throws`, class + message
- `"group=\"name\""` → group = name

Use regex for `throws(...)` and `group="..."` parsing. Simple string matching for keyword-only attributes.

#### Tests

```
tests/Unit/Parser/MarkdownParserTest.php
  - parses markdown string into AST
  - returns Document node

tests/Unit/Parser/AttributeParserTest.php
  - parses empty string (no attributes)
  - parses "ignore"
  - parses "no_run"
  - parses "throws" (bare)
  - parses "throws(ClassName)"
  - parses "throws(ClassName, \"message\")"
  - parses "parse_error"
  - parses "setup"
  - parses "teardown"
  - parses "group=\"name\""
  - strips Shiki metadata "{1,4-6}"
  - case-insensitive language detection
  - ignores unknown attributes (treats as no attribute)

tests/Unit/Parser/CodeBlockExtractorTest.php
  - extracts PHP blocks from markdown
  - skips non-PHP blocks
  - skips blocks without language
  - handles case-insensitive "PHP"
  - strips <?php tag
  - strips Shiki [!code --] lines
  - strips Shiki [!code ++] markers (keeps code)
  - preserves line numbers from source markdown
  - passes attributes to CodeBlock
  - passes assertions to CodeBlock
```

#### Test Fixtures

```
tests/Fixtures/basic.md           — simple PHP blocks with Output assertions
tests/Fixtures/mixed-languages.md — PHP + JS + Python blocks
tests/Fixtures/attributes.md      — all attribute types
tests/Fixtures/vitepress.md       — Shiki metadata and markers
tests/Fixtures/no-php.md          — no PHP blocks at all
tests/Fixtures/empty.md           — empty file
```

#### Deliverables

- [ ] Create `MarkdownParser`
- [ ] Create `AttributeParser` with all patterns
- [ ] Create `CodeBlockExtractor`
- [ ] Create Shiki stripping logic
- [ ] Create `<?php` stripping logic
- [ ] Create test fixtures (markdown files)
- [ ] Write tests for all parser components
- [ ] All tests pass, phpstan clean

---

### Step 1.3 — Assertion Parsing

Extract assertion comments from PHP code within a block.

#### `src/Assertion/AssertionParser.php`

Scans code lines to find and extract assertion comments.

```php
final readonly class AssertionParser
{
    /**
     * @return array{assertions: Assertion[], executableCode: string}
     */
    public function parse(string $code): array { ... }
}
```

Algorithm (line-by-line):
1. For each line, check if it matches an assertion pattern
2. If yes, create the appropriate `Assertion` object and record its position
3. Build executable code by removing assertion comment lines
4. Return both the assertions and the cleaned executable code

Assertion patterns to detect:

```
// Output: <expected>           → single-line OutputAssertion
// Output:                      → start of multi-line OutputAssertion
//   <continuation line>        → continuation (must follow // Output:)
// Expect: <expression>         → ExpectAssertion
// OutputContains: <substring>  → OutputContainsAssertion (Phase 2)
// OutputMatches: <regex>       → OutputMatchesAssertion (Phase 2)
// OutputJson: <json>           → OutputJsonAssertion (Phase 2)
```

For Phase 1, only `// Output:` (single and multi-line) is needed.

**Multi-line output parsing:**

```php
print_r(['a' => 1]);
// Output:
// Array
// (
//     [a] => 1
// )
```

Rules:
- `// Output:` with no value on the same line starts multi-line mode
- Subsequent lines starting with `// ` (note the space) are continuation lines
- First line NOT starting with `// ` ends the multi-line block
- Continuation lines have `// ` prefix stripped

**Code segmentation for per-statement output matching:**

The parser also needs to split code into segments at assertion boundaries. Each segment is the executable code between two assertions (or from start/end of block).

```php
final readonly class CodeSegment
{
    public function __construct(
        public string $code,                    // Executable PHP code for this segment
        public ?OutputAssertion $outputAssertion, // Output assertion following this segment (if any)
    ) {}
}
```

The `AssertionParser` returns:
- `Assertion[]` — all assertions found
- `string $executableCode` — full code with assertion comments stripped
- `CodeSegment[]` — code segments split at `// Output:` boundaries (for per-statement matching)
- `ExpectAssertion[]` — expect assertions (evaluated after full execution)

#### Tests

```
tests/Unit/Assertion/AssertionParserTest.php
  - finds single-line // Output:
  - finds multi-line // Output:
  - finds // Expect:
  - strips assertion comments from executable code
  - preserves non-assertion comments
  - handles multiple assertions in one block
  - handles block with no assertions
  - handles mixed Output and Expect assertions
  - segments code at Output assertion boundaries
  - multi-line output continuation parsing
  - trims expected output whitespace correctly
  - preserves internal indentation in multi-line output
```

#### Deliverables

- [ ] Create `AssertionParser`
- [ ] Implement single-line `// Output:` detection
- [ ] Implement multi-line `// Output:` detection
- [ ] Implement `// Expect:` detection
- [ ] Implement assertion stripping from code
- [ ] Implement code segmentation at assertion boundaries
- [ ] Write tests
- [ ] All tests pass, phpstan clean

---

### Step 1.4 — Code Execution (Process Isolation)

Execute code blocks in isolated PHP processes. No `eval()` — every block runs as a separate PHP subprocess via `proc_open()`.

#### Architecture Overview

```
CodeBlock → CodeGenerator → temp .php file → ProcessRunner → proc_open() → ExecutionResult
                                                   ↓
                                              stdout = user output
                                              stderr = JSON result metadata
```

Three classes collaborate:

1. **`CodeGenerator`** — generates an instrumented PHP file from a CodeBlock
2. **`ProcessRunner`** — executes a PHP file as a subprocess, captures output
3. **`Executor`** — orchestrates the flow, handles attributes, builds results

#### `src/Executor/CodeGenerator.php`

Generates a self-contained PHP file that instruments the user's code with output capture and assertion evaluation.

```php
final readonly class CodeGenerator
{
    /**
     * Generate an instrumented PHP file for a code block.
     * Returns the absolute path to the generated temp file.
     */
    public function generate(CodeBlock $block): string { ... }
}
```

**Generated file structure:**

For a block like:
```php
$x = 1 + 2;
echo $x;
// Output: 3
// Expect: $x === 3
```

The generator produces:

```php
<?php declare(strict_types=1);

// Segment 0: capture output
ob_start();
$x = 1 + 2;
echo $x;
$__segment_0_output = ob_get_clean();

// Collect results
$__results = [
    'segments' => [
        ['index' => 0, 'output' => $__segment_0_output],
    ],
    'expects' => [],
    'error' => null,
    'exception' => null,
];

// Expect assertions (user variables are in scope)
try {
    $__results['expects'][] = [
        'expression' => '$x === 3',
        'result' => (bool)($x === 3),
        'line' => 4,
    ];
} catch (\Throwable $__e) {
    $__results['expects'][] = [
        'expression' => '$x === 3',
        'result' => false,
        'error' => $__e->getMessage(),
        'line' => 4,
    ];
}

// Write results to stderr as JSON
fwrite(STDERR, json_encode($__results, JSON_THROW_ON_ERROR));
```

**Key design decisions:**

- User code runs at the top level of the generated file — no closures, no eval, no scope tricks
- Each code segment gets its own `ob_start()`/`ob_get_clean()` pair
- Variables from segment 0 are naturally available in segment 1, etc. (same file scope)
- `// Expect:` assertions are evaluated after all segments, in the same scope
- Results are written to stderr as JSON, so stdout stays clean for user output
- The generated file catches exceptions at two levels:
  1. Around each segment (for output capture)
  2. Around expect assertions (for expression evaluation)
- `$__` prefix for internal variables (minimal collision risk, and user code is visible for review)

**For `throws` attribute blocks:**

The entire user code is wrapped in a try/catch:

```php
<?php declare(strict_types=1);

$__results = ['error' => null, 'exception' => null, 'segments' => [], 'expects' => []];

try {
    // user code here
    $__results['error'] = 'Expected exception was not thrown';
} catch (\Throwable $__e) {
    $__results['exception'] = [
        'class' => get_class($__e),
        'message' => $__e->getMessage(),
        'code' => $__e->getCode(),
    ];
}

fwrite(STDERR, json_encode($__results, JSON_THROW_ON_ERROR));
```

**For `parse_error` attribute blocks:**

The generator writes the raw user code (without instrumentation) and the runner checks if the process exits with a parse error.

**Temp file management:**

- Files are written to `sys_get_temp_dir().'/doctest/'`
- File names: `doctest_<hash>.php` where hash is based on file + line
- Temp directory is cleaned up after each run

#### `src/Executor/ProcessRunner.php`

Executes a PHP file as a subprocess.

```php
final readonly class ProcessRunner
{
    public function __construct(
        private int $timeout = 30,
        private string $memoryLimit = '256M',
    ) {}

    public function run(string $phpFilePath): ProcessResult { ... }
}
```

**`ProcessResult` value object:**

```php
final readonly class ProcessResult
{
    public function __construct(
        public string $stdout,     // User's output
        public string $stderr,     // JSON results (or error messages)
        public int $exitCode,      // Process exit code
        public float $duration,    // Execution time in seconds
    ) {}
}
```

**Process execution via `proc_open()`:**

```php
$descriptors = [
    0 => ['pipe', 'r'],  // stdin
    1 => ['pipe', 'w'],  // stdout
    2 => ['pipe', 'w'],  // stderr
];

$cmd = sprintf(
    'php -d memory_limit=%s -d max_execution_time=%d %s',
    escapeshellarg($this->memoryLimit),
    $this->timeout,
    escapeshellarg($phpFilePath),
);

$process = proc_open($cmd, $descriptors, $pipes);
```

**Timeout enforcement:**

- Use `-d max_execution_time=N` for the child process itself
- Additionally, the runner monitors wall-clock time and kills the process if it exceeds timeout
- Use `stream_select()` with timeout or a simple loop with `microtime()`

**PHP binary discovery:**

Use `PHP_BINARY` constant to get the path to the current PHP interpreter. This ensures the subprocess uses the same PHP version.

#### `src/Executor/Executor.php`

The orchestrator.

```php
final class Executor
{
    public function __construct(
        private readonly CodeGenerator $codeGenerator,
        private readonly ProcessRunner $processRunner,
    ) {}

    public function execute(CodeBlock $block): ExecutionResult { ... }
}
```

**Execution algorithm:**

1. Check attributes:
   - `ignore` → return skipped result immediately
   - `no_run` → syntax check only (use `php -l` via ProcessRunner)
   - `parse_error` → run `php -l`, expect it to fail
   - `throws` → generate throws-instrumented file, check exception in results
   - `group` → delegate to group executor (Phase 2)

2. For a regular block:
   a. `CodeGenerator::generate($block)` → temp file path
   b. `ProcessRunner::run($tempFile)` → ProcessResult
   c. Parse JSON from stderr → segment outputs + expect results
   d. Compare stdout per segment against `// Output:` assertions
   e. Check expect assertion results
   f. Build `ExecutionResult`
   g. Clean up temp file

3. For `no_run` / `parse_error`:
   a. Write raw code to temp file (no instrumentation)
   b. Run `php -l tempfile.php` via ProcessRunner
   c. `no_run`: expect exit code 0 (valid syntax)
   d. `parse_error`: expect exit code non-zero (invalid syntax)

**Advantages of process isolation:**

- No `eval()` — no variable collision, no scope hacks
- User code can safely use `exit()`, `die()`, `define()`, `set_error_handler()`
- Each block is truly isolated — no state leaks between blocks
- Timeout/memory limits enforced at OS level (not just PHP level)
- No `$_doctest_` prefix needed
- No `extract()`/`get_defined_vars()` needed
- Cleaner error reporting (process crash ≠ tool crash)

#### Tests

```
tests/Unit/Executor/CodeGeneratorTest.php
  - generates valid PHP file for simple echo block
  - generates segment-based output capture
  - generates multiple segments for multiple assertions
  - generates expect assertion evaluation
  - generates throws wrapper for throws attribute
  - generates raw code for parse_error attribute
  - writes results to stderr as JSON
  - handles block with no assertions
  - handles mixed Output and Expect assertions
  - temp file is valid PHP (php -l passes)

tests/Unit/Executor/ProcessRunnerTest.php
  - runs a PHP file and captures stdout
  - captures stderr
  - returns exit code
  - measures execution duration
  - enforces timeout (kills long-running process)
  - passes memory limit to child process
  - uses PHP_BINARY for subprocess

tests/Unit/Executor/ExecutorTest.php
  - executes simple echo and captures output
  - executes code with no output
  - captures exceptions via throws attribute
  - returns skipped result for ignore attribute
  - syntax checks no_run blocks via php -l
  - detects parse errors for parse_error blocks
  - evaluates Expect expressions
  - Expect with falsy result fails
  - variables from code available in Expect
  - multiple Output assertions per block
  - block with no assertions passes if no exception
  - cleans up temp files after execution
  - handles process crash gracefully
  - handles process timeout gracefully
```

#### Test Fixtures

```
tests/Fixtures/simple-output.md      — echo "hello"; // Output: hello
tests/Fixtures/multi-output.md       — multiple Output assertions
tests/Fixtures/expect.md             — Expect assertions
tests/Fixtures/mixed-assertions.md   — Output + Expect in same block
tests/Fixtures/no-assertions.md      — blocks with no assertions (smoke test)
tests/Fixtures/error-block.md        — block that throws
tests/Fixtures/exit-block.md         — block that calls exit()
tests/Fixtures/parse-error-block.md  — block with syntax error
```

#### Deliverables

- [ ] Create `CodeGenerator`
- [ ] Implement segment-based code generation with `ob_start()`/`ob_get_clean()`
- [ ] Implement `// Expect:` evaluation in generated code
- [ ] Implement `throws` wrapper generation
- [ ] Implement `parse_error` raw code generation
- [ ] Implement temp file management (create/cleanup)
- [ ] Create `ProcessResult` value object
- [ ] Create `ProcessRunner` with `proc_open()`
- [ ] Implement timeout enforcement (wall-clock + `max_execution_time`)
- [ ] Implement memory limit passing to child process
- [ ] Create `Executor` orchestrator
- [ ] Implement `ignore` handling (skip)
- [ ] Implement `no_run` handling (syntax check via `php -l`)
- [ ] Implement `parse_error` handling (expect syntax failure)
- [ ] Implement `throws` handling (expect exception)
- [ ] Handle process errors gracefully (no crash, report error)
- [ ] Write tests for all three classes
- [ ] All tests pass, phpstan clean

---

### Step 1.5 — Output Comparison

Compare expected vs actual output and generate diffs.

#### `src/Comparison/Normalizer.php`

Normalize output strings before comparison.

```php
final readonly class Normalizer
{
    public function normalize(string $output): string { ... }
}
```

Normalization steps (in order):
1. Convert `\r\n` to `\n`
2. Remove trailing whitespace from each line
3. Remove leading/trailing blank lines
4. Normalize to single trailing newline (or empty string if no content)

#### `src/Comparison/OutputComparator.php`

Compare expected and actual output.

```php
final readonly class OutputComparator
{
    public function __construct(
        private Normalizer $normalizer,
    ) {}

    public function compare(string $expected, string $actual): ComparisonResult { ... }
}
```

For Phase 1, exact match (after normalization) only. Phase 2 adds contains, regex, JSON, wildcards.

#### `src/Comparison/DiffGenerator.php`

Generate a human-readable diff when comparison fails.

```php
final readonly class DiffGenerator
{
    public function generate(string $expected, string $actual): string { ... }
}
```

Use a simple line-by-line diff algorithm. For each line:
- If lines match: prefix with `  ` (two spaces)
- If only in expected: prefix with `- ` (red in console)
- If only in actual: prefix with `+ ` (green in console)

Consider using `SebastianBergmann\Diff` (from PHPUnit's diff package) — it's a solid PHP diffing library. Add as dependency if needed, or implement a simple LCS-based diff.

#### Tests

```
tests/Unit/Comparison/NormalizerTest.php
  - converts CRLF to LF
  - removes trailing whitespace from lines
  - removes leading blank lines
  - removes trailing blank lines
  - normalizes to single trailing newline
  - handles empty string
  - preserves internal blank lines
  - preserves internal indentation

tests/Unit/Comparison/OutputComparatorTest.php
  - exact match passes
  - exact match fails
  - normalized match passes (trailing whitespace difference)
  - normalized match passes (CRLF vs LF)
  - empty expected vs empty actual passes
  - multi-line comparison

tests/Unit/Comparison/DiffGeneratorTest.php
  - single line diff
  - multi-line diff
  - addition only
  - removal only
  - mixed changes
  - identical strings produce empty diff
```

#### Deliverables

- [ ] Create `Normalizer`
- [ ] Create `OutputComparator` (exact match)
- [ ] Create `DiffGenerator`
- [ ] Write tests
- [ ] All tests pass, phpstan clean

---

### Step 1.6 — Console Reporter

Report test results to the terminal.

#### `src/Reporter/ConsoleReporter.php`

```php
final class ConsoleReporter
{
    public function __construct(
        private readonly resource $output = STDOUT,
    ) {}

    public function reportFile(string $file): void { ... }
    public function reportResult(ExecutionResult $result): void { ... }
    public function reportSummary(array $results): void { ... }
}
```

Output format (from spec):

```
DocTest v1.0.0

docs/getting-started.md
  ✓ Block (line 42)
  ✗ Block (line 67)
    Expected: hello
    Actual:   world
    Diff:
    - hello
    + world
  ○ Block (line 89) [ignore]

─────────────────────────────────────────
Files:     1
Blocks:    3 (1 ignored)
Passed:    1
Failed:    1
Duration:  0.12s
```

Symbols:
- `✓` — passed (green)
- `✗` — failed (red)
- `○` — skipped/ignored (gray)

Use ANSI escape codes for colors. Detect TTY for color support.

Block descriptions: Use the heading above the code block if available, otherwise `"Block (line N)"`.

#### Tests

Test with a string stream instead of STDOUT:

```
tests/Unit/Reporter/ConsoleReporterTest.php
  - reports passing result with checkmark
  - reports failing result with diff
  - reports skipped result
  - reports file header
  - reports summary statistics
  - no colors when not a TTY
```

#### Deliverables

- [ ] Create `ConsoleReporter`
- [ ] Implement pass/fail/skip symbols
- [ ] Implement diff display for failures
- [ ] Implement summary statistics
- [ ] Implement ANSI color support
- [ ] Write tests
- [ ] All tests pass, phpstan clean

---

### Step 1.7 — Configuration

Load and manage configuration.

#### `src/Config/DocTestConfig.php`

```php
final readonly class DocTestConfig
{
    public function __construct(
        public array $paths = ['docs', 'README.md'],
        public array $exclude = [],
        public int $timeout = 30,
        public string $memoryLimit = '256M',
        public bool $stopOnFailure = false,
        public bool $verbose = false,
    ) {}

    public static function load(?string $configPath = null): self { ... }
}
```

Config loading order:
1. If `--config=path` CLI arg provided, use that file
2. Look for `doctest.php` in current working directory
3. Use defaults

Config file returns a PHP array (same format as spec).

#### File Discovery

Part of DocTestConfig or a separate `FileFinder`:

```php
final readonly class FileFinder
{
    /** @return string[] List of markdown file paths */
    public function find(array $paths, array $exclude): array { ... }
}
```

Algorithm:
1. For each path in config `paths`:
   - If it's a file: add to list
   - If it's a directory: glob for `**/*.md` recursively
2. Apply exclude patterns (glob matching)
3. Return sorted, unique list

#### Tests

```
tests/Unit/Config/DocTestConfigTest.php
  - loads from array
  - uses defaults for missing keys
  - loads from file path
  - returns default config when no file exists

tests/Unit/Config/FileFinderTest.php
  - finds markdown files in directory
  - finds single file
  - excludes patterns
  - returns empty for nonexistent path
  - recursive directory search
```

#### Deliverables

- [ ] Create `DocTestConfig`
- [ ] Create `FileFinder`
- [ ] Implement config file loading
- [ ] Implement file discovery with glob
- [ ] Write tests
- [ ] All tests pass, phpstan clean

---

### Step 1.8 — Main Entry Point & CLI

Wire everything together.

#### `src/DocTest.php`

```php
final class DocTest
{
    public function __construct(
        private readonly DocTestConfig $config,
    ) {}

    /** @return int Exit code */
    public function run(array $argv): int { ... }

    /** @return ExecutionResult[] */
    public function testFile(string $filePath): array { ... }

    /** @return ExecutionResult[] */
    public function testAll(): array { ... }
}
```

`run()` algorithm:
1. Parse CLI arguments (file paths, --dry-run, --stop-on-failure, --filter, -v)
2. Discover markdown files (from args or config paths)
3. If no files found → exit code 3
4. For each file:
   a. Parse markdown → extract code blocks
   b. Filter blocks (--filter pattern)
   c. For each block:
      - Execute
      - Report result
      - If --stop-on-failure and failed → stop
5. Report summary
6. Return exit code (0 all passed, 1 some failed)

#### CLI Argument Parsing

Simple argv parsing (no external library needed for Phase 1):

```php
final readonly class CliArgs
{
    public function __construct(
        public array $files = [],           // Positional args
        public ?string $filter = null,      // --filter=PATTERN
        public bool $dryRun = false,        // --dry-run
        public bool $stopOnFailure = false, // --stop-on-failure
        public int $verbosity = 0,          // -v, -vv, -vvv
    ) {}

    public static function parse(array $argv): self { ... }
}
```

#### Integration Tests

End-to-end tests using fixture markdown files:

```
tests/Integration/EndToEndTest.php
  - runs simple output test and passes
  - runs failing output test and reports failure
  - runs ignored block and skips it
  - runs multiple files
  - respects --filter option
  - respects --dry-run (no execution)
  - respects --stop-on-failure
  - exit code 0 when all pass
  - exit code 1 when any fail
  - exit code 3 when no tests found
  - handles empty markdown file
  - handles markdown with no PHP blocks
```

#### Deliverables

- [ ] Create `CliArgs` parser
- [ ] Create `DocTest` main class
- [ ] Wire Parser → Extractor → AssertionParser → Executor → Comparator → Reporter
- [ ] Implement `--dry-run`
- [ ] Implement `--filter`
- [ ] Implement `--stop-on-failure`
- [ ] Implement verbosity levels
- [ ] Implement exit codes
- [ ] Update `bin/doctest` to use `DocTest::run()`
- [ ] Write integration tests with fixture files
- [ ] All tests pass, phpstan clean
- [ ] Manual test: create a sample.md and run `vendor/bin/doctest sample.md`

---

## Phase 1 Completion Criteria

All of these must be true before moving to Phase 2:

- [ ] `vendor/bin/doctest docs/sample.md` runs and reports results
- [ ] `// Output:` single-line assertions work
- [ ] `// Output:` multi-line assertions work
- [ ] `// Expect:` assertions work
- [ ] `ignore` attribute skips blocks
- [ ] `no_run` attribute checks syntax without executing
- [ ] `throws` attribute expects exceptions
- [ ] `parse_error` attribute expects syntax errors
- [ ] `--dry-run` parses without executing
- [ ] `--filter` filters blocks
- [ ] `--stop-on-failure` stops at first failure
- [ ] Exit codes are correct (0, 1, 3)
- [ ] Console output shows ✓/✗/○ with diffs on failure
- [ ] All unit tests pass
- [ ] All integration tests pass
- [ ] PHPStan level max clean
- [ ] Pint formatted

---

## Phase 2: Assertions & Attributes

Builds on Phase 1 foundation. Only start after Phase 1 completion criteria are met.

### Step 2.1 — Wildcard Matching

Add wildcard placeholder support to output comparison.

- Implement wildcard-to-regex conversion in `OutputComparator`
- `{{any}}` → `.+?`
- `{{int}}` → `-?\d+`
- `{{float}}` → `-?\d+\.?\d*`
- `{{uuid}}` → `[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}`
- `{{datetime}}` → ISO 8601 regex
- `{{date}}` → `\d{4}-\d{2}-\d{2}`
- `{{time}}` → `\d{2}:\d{2}:\d{2}`
- `{{...}}` → `[\s\S]*`

### Step 2.2 — Subset Output Assertions

- `// OutputContains:` — substring match
- `// OutputMatches:` — regex match
- `// OutputJson:` — JSON structural comparison

### Step 2.3 — Group Execution

- Implement `group="name"` shared execution context
- Collect all blocks with same group name from a file
- Concatenate all blocks into a single generated PHP file (in document order)
- Run as one process — variables naturally shared (same file scope)
- Each block's assertions are still evaluated independently within the generated file
- `CodeGenerator` gets a `generateGroup(array $blocks): string` method

### Step 2.4 — Setup/Teardown

- `setup` blocks execute before all other blocks in a file
- `teardown` blocks execute after all blocks in a file
- Multiple setup/teardown blocks execute in document order

### Step 2.5 — Configuration File

- Implement `doctest.php` config file loading
- Support all config keys from spec
- Environment variable overrides

---

## Technical Decisions Log

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Markdown parser | `league/commonmark` | AST-based, well-maintained, PHP ecosystem |
| Code execution | `proc_open()` process isolation | No eval() — every block runs in a separate PHP process. Safer, cleaner, no scope hacks |
| Output capture | `ob_start()`/`ob_get_clean()` in generated file | Standard PHP output buffering, per-segment capture |
| Result communication | JSON via stderr | Stdout stays clean for user output, stderr carries structured metadata |
| Scope sharing (groups) | Concatenate blocks into single file | Same file = shared scope naturally. No extract()/get_defined_vars() needed |
| Diff generation | Simple line-by-line | Sufficient for MVP, can enhance later |
| CLI arg parsing | Custom (no library) | Few options, no need for heavy dependency |
| Test framework | Pest | Modern, expressive, PHP-native |
| Code style | Pint (PER preset) | Laravel-adjacent, industry standard |
| Static analysis | PHPStan level max | Maximum strictness from day one |
| Syntax checking | `php -l` via subprocess | Native PHP lint, used for no_run and parse_error attributes |

---

## Dependency Graph

```
Step 0: Scaffolding
  ↓
Step 1.1: Value Objects
  ↓
Step 1.2: Markdown Parsing ←── depends on 1.1 (CodeBlock, Attributes)
  ↓
Step 1.3: Assertion Parsing ←── depends on 1.1 (Assertion types)
  ↓
Step 1.4: Code Execution ←── depends on 1.1, 1.3 (CodeBlock, segments)
  ↓
Step 1.5: Output Comparison ←── independent (can parallel with 1.4)
  ↓
Step 1.6: Console Reporter ←── depends on 1.4 (ExecutionResult)
  ↓
Step 1.7: Configuration ←── independent (can parallel with earlier steps)
  ↓
Step 1.8: Main Entry Point ←── depends on ALL above
```

Steps 1.5 and 1.7 can be developed in parallel with other steps since they have no upstream dependencies on execution or parsing internals.

---

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| Process startup overhead | ~20-50ms per block, slower than eval | Acceptable tradeoff for safety; group blocks to reduce process count |
| Temp file cleanup failure | Orphaned files in temp directory | Use try/finally, register shutdown handler, prefix files for easy cleanup |
| `php` binary not in PATH | ProcessRunner can't execute | Use `PHP_BINARY` constant (current interpreter), fall back to `php` |
| Child process crash without stderr output | No JSON results to parse | Detect by exit code, report raw stderr as error message |
| Multi-line output parsing edge cases | Wrong assertion boundaries | Thorough test fixtures, fuzzing |
| `league/commonmark` AST changes | Code block detection breaks | Pin major version, test against specific version |
| Timeout enforcement on Windows | `max_execution_time` may not work for all cases | Wall-clock monitoring in ProcessRunner as backup |
| Large markdown files | Memory/performance issues | Lazy iteration, don't load all blocks into memory |
| Generated file has syntax error | Block execution fails cryptically | Validate generated files with `php -l` in debug mode |
