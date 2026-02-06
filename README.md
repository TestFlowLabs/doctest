# DocTest

A PHP documentation testing tool that validates code examples in your markdown files. Write documentation with confidence — if it compiles and runs, it stays correct.

## Why?

Code examples in documentation rot. APIs change, methods get renamed, return types evolve — but the docs stay frozen. DocTest extracts PHP code blocks from your markdown files, executes them in isolated processes, and verifies their output. If an example breaks, you'll know immediately.

- **Tested documentation** — code examples are verified on every CI run
- **Invisible assertions** — HTML comments keep assertions hidden from rendered docs
- **Process isolation** — each example runs in its own process, no side effects
- **Zero setup** — run `vendor/bin/doctest` and it just works

## Installation

```bash
composer require --dev testflowlabs/doctest
```

## Quick Start

Write a PHP code block in any markdown file with an assertion in an HTML comment:

````markdown
```php
echo 'Hello, World!';
```
<!-- doctest: Hello, World! -->
````

Run it:

```bash
vendor/bin/doctest
```

DocTest scans `docs/` and `README.md` by default. That's it.

## What Can It Do?

DocTest supports six assertion types, wildcards for dynamic output, code block attributes (`ignore`, `no_run`, `throws`, `parse_error`), grouped examples with shared state, and a `bootstrap` config for loading your project's autoloader. A few examples:

```php
echo 2 + 3;
```
<!-- doctest: 5 -->

```php
$prices = [10.5, 20.0, 30.75];
$total = array_sum($prices); // => 61.25
```

```php
echo json_encode(['tool' => 'DocTest']);
```
<!-- doctest-json: {"tool":"DocTest"} -->

For the full feature set — assertions, attributes, wildcards, groups, configuration, CLI options, CI integration, and framework bootstrap — see the **[Documentation](docs/)**.

## Requirements

- PHP 8.4+

## License

MIT
