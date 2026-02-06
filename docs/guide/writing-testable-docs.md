# Writing Testable Documentation

::: tip A New Concept
"Testable Documentation" isn't an established term in the industry. Individual tools exist — Python's `doctest` (1999), Rust's doc tests, Go's `Example` functions, Elixir's doctests — but no one has formalized the underlying principles into a unified guide. This is our attempt to do that. We've studied what works across ecosystems and distilled it into a set of language-agnostic principles with PHP-specific examples. Consider this a living document — we'd love your feedback on [GitHub](https://github.com/testflowlabs/doctest/issues).
:::

Code examples in documentation rot. APIs change, methods get renamed, return types evolve — but the docs stay frozen. Testable documentation solves this by making every example verifiable.

This guide covers the principles and patterns for writing documentation examples that stay correct over time.

## Why Documentation Breaks

Documentation drift is inevitable without automated verification:

1. A method signature changes, but the example still shows the old signature
2. A default value changes, but the example still shows the old output
3. A class gets renamed, but the example still references the old name
4. A feature is removed, but the example still demonstrates it

The cost is real: developers copy broken examples, waste time debugging, and lose trust in the documentation.

The fix: **treat every code example as a test**.

## The Core Idea

Every code example should be:

- **Executable** — it can actually run
- **Verifiable** — the expected output is declared
- **Isolated** — it doesn't depend on hidden state
- **Automated** — verification happens in CI, not by hand

This is not new. Python's `doctest` module has done this since 1999. Rust compiles and runs every code example in documentation by default. Go's `Example` functions serve as both tests and docs. Elixir's `ExUnit.DocTest` extracts tests from module documentation.

DocTest brings this practice to PHP markdown documentation.

## Ten Principles

### 1. Every Example Should Run

If a reader copies your example into a file and runs it, it should work. No hidden `require` statements, no assumed variables, no implicit setup.

````markdown
<!-- Bad: What is $user? Where does it come from? -->
```php
echo $user->name;
```

<!-- Good: Self-contained, runs as-is -->
```php
$name = 'Alice';
echo strtoupper($name);
```
<!-- doctest: ALICE -->
````

### 2. One Concept Per Block

Each code block should demonstrate exactly one thing. Don't combine unrelated concepts in a single example.

````markdown
<!-- Bad: Teaches arrays AND string functions AND math -->
```php
$items = ['apple', 'banana'];
echo strtoupper($items[0]);
echo array_sum([1, 2, 3]);
```

<!-- Good: Each block teaches one concept -->
```php
$items = ['apple', 'banana'];
echo count($items);
```
<!-- doctest: 2 -->

```php
echo strtoupper('hello');
```
<!-- doctest: HELLO -->
````

### 3. Prefer `echo` Over `var_dump`

`echo` produces predictable, readable output. `var_dump` includes type annotations and formatting that varies across PHP versions.

````markdown
<!-- Bad: var_dump output is fragile -->
```php
var_dump([1, 2, 3]);
```

<!-- Good: Predictable output -->
```php
echo json_encode([1, 2, 3]);
```
<!-- doctest-json: [1, 2, 3] -->
````

### 4. Handle Dynamic Output with Wildcards

Timestamps, UUIDs, and other dynamic values change between runs. Use wildcards instead of hardcoding values.

````markdown
<!-- Bad: Hardcoded timestamp, fails tomorrow -->
```php
echo date('Y-m-d');
```
<!-- doctest: 2025-01-15 -->

<!-- Good: Wildcard matches any date -->
```php
echo date('Y-m-d');
```
<!-- doctest: {{date}} -->
````

### 5. Mark Non-Runnable Examples Explicitly

Some examples can't run standalone — they need a database, an API, or a framework. Mark them clearly instead of leaving them to fail silently.

````markdown
<!-- Bad: Will fail at runtime -->
```php
$users = DB::table('users')->get();
```

<!-- Good: Syntax validated, not executed -->
```php no_run
$users = DB::table('users')->get();
```
````

Use `no_run` when syntax should be validated. Use `ignore` when even syntax checking isn't relevant.

### 6. Use Groups for Related Examples

When examples build on each other, use groups to share state. This is better than asking readers to imagine variables from previous blocks.

````markdown
```php setup group="cart"
$cart = [];
```

```php group="cart"
$cart[] = ['item' => 'Widget', 'price' => 9.99];
echo count($cart);
```
<!-- doctest: 1 -->

```php group="cart"
$total = array_sum(array_column($cart, 'price'));
echo $total;
```
<!-- doctest: 9.99 -->
````

### 7. Document Error Conditions

Show what happens when things go wrong. Use `throws` to verify exception behavior.

````markdown
```php throws(InvalidArgumentException)
function divide(int $a, int $b): float {
    if ($b === 0) {
        throw new InvalidArgumentException('Division by zero');
    }
    return $a / $b;
}

divide(10, 0);
```
````

### 8. Choose the Right Assertion

Use the simplest assertion that verifies correctness:

| Situation | Assertion | Why |
|-----------|-----------|-----|
| Known exact output | `<!-- doctest: -->` | Simple and clear |
| Output with dynamic parts | `<!-- doctest: -->` + wildcards | Readable patterns |
| Only part of output matters | `<!-- doctest-contains: -->` | Flexible |
| JSON output | `<!-- doctest-json: -->` | Key order independent |
| Return value, no output | `// =>` or `<!-- doctest-expect: -->` | Value verification |

Don't use `<!-- doctest-matches: /regex/ -->` when wildcards would do. Regex is powerful but harder to read.

### 9. Keep Setup Minimal

If an example needs setup, keep it as small as possible. Use bootstrap files for framework dependencies instead of repeating boilerplate in every block.

````markdown
<!-- Bad: 10 lines of setup for a 2-line example -->
```php
require_once 'vendor/autoload.php';
$app = new Application();
$app->boot();
$config = $app->config();
$db = $config->database();
$connection = $db->connect();
// Finally, the actual example:
$users = $connection->query('SELECT * FROM users');
echo count($users);
```

<!-- Good: Setup in bootstrap, example is focused -->
```php group="users"
$count = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
echo $count;
```
<!-- doctest: {{int}} -->
````

### 10. Test-Driven Documentation

Write the test before writing the example. This mirrors TDD:

1. **Write the assertion** — what should the output be?
2. **Write the code** — make the example produce that output
3. **Verify** — run DocTest to confirm

This approach forces you to think about what the reader should learn from each example.

## Anti-Patterns

### Hidden State

```php
// BAD: Where does $config come from?
echo $config['database'];
```

The reader can't run this. Either make the example self-contained or use a group with a setup block.

### Testing the Language, Not Your Code

````markdown
<!-- BAD: This tests PHP, not your library -->
```php
echo strlen('hello');
```
<!-- doctest: 5 -->
````

Focus examples on your library's API, not on PHP built-ins the reader already knows.

### Overly Complex Examples

If an example needs more than 15 lines, it's probably doing too much. Split it into smaller, focused examples or use a group.

### Fragile Assertions

````markdown
<!-- BAD: Breaks if whitespace or formatting changes -->
```php
print_r(['a' => 1, 'b' => 2]);
```
<!-- doctest: Array
(
    [a] => 1
    [b] => 2
)
-->
````

Use `<!-- doctest-json: -->` for structured data. It's whitespace and key-order independent.

### Ignoring Everything

````markdown
<!-- BAD: Marking everything as ignore defeats the purpose -->
```php ignore
echo 'This is fine';
```
````

If an example can run, let it run. Only use `ignore` for genuinely non-runnable code.
