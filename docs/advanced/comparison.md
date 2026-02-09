# Cross-Language Comparison

How does DocTest compare to doctest implementations in other languages? This page provides a feature-by-feature comparison.

## Implementations

| Language | Tool | Since |
|----------|------|-------|
| **Python** | `doctest` (stdlib) | 1999  |
| **Rust** | `rustdoc --test` | 2015  |
| **Elixir** | `ExUnit.DocTest` | 2014  |
| **Go** | `go test` (testable examples) | 2012  |
| **PHP** | **DocTest** | 2026  |

## Feature Matrix

| Feature | Python | Rust | Elixir | Go | **DocTest** |
|---------|--------|------|--------|----|-------------|
| Exact output matching | Yes | Yes | Yes | Yes | Yes |
| Pattern/regex matching | `+ELLIPSIS` | — | — | — | `output-matches` |
| Partial output matching | — | — | — | — | `output-contains` |
| JSON comparison | — | — | — | — | `output-json` |
| Inline result assertion | — | — | `iex>` / `...>` | — | `// =>` |
| Wildcard patterns | `...` only | — | — | — | 8 patterns (<code v-pre>{{uuid}}</code>, <code v-pre>{{date}}</code>, etc.) |
| Unordered output | — | — | — | `Unordered output:` | — |
| Exception testing | `Traceback` block | `should_panic` | `** (Error)` | — | `throws` attribute |
| Parse error testing | — | `compile_fail` | — | — | `parse_error` attribute |
| Skip/ignore blocks | `+SKIP` | `ignore` | — | — | `ignore` attribute |
| No-run (syntax only) | — | `no_run` | — | — | `no_run` attribute |
| Block grouping | — | — | — | — | `group` attribute |
| Setup/teardown | — | — | — | — | `setup` / `teardown` |
| Framework bootstrap | — | `extern crate` | — | — | `bootstrap` attribute |
| Parallel execution | — | Yes | Yes | — | `--parallel` flag |
| Auto-update expected | `ACCEPT` mode | `--bless` | — | — | — |
| Watch mode | — | `cargo watch` | `mix test --stale` | — | — |
| Process isolation | — | Yes | — | Yes | Yes |
| Normalize whitespace | `+NORMALIZE_WHITESPACE` | — | — | — | Automatic |
| Debug dump | — | — | `IO.inspect` | — | `dd()` |
| Hidden setup lines | — | `# hidden line` | — | — | `// # hidden` |

## Key Differences

### Assertion Variety

Python and Go support only exact output matching (Python adds `+ELLIPSIS` for basic wildcards). Rust relies on compilation and panic behavior rather than output assertions. Elixir matches return values via `iex>` prompts.

DocTest offers 6 distinct assertion types, allowing documentation authors to choose the right level of strictness for each code block.

### Wildcard System

<div v-pre>

Python's `...` is the only wildcard available in other implementations -- it matches anything. DocTest provides 8 semantic wildcards (`{{uuid}}`, `{{date}}`, `{{datetime}}`, `{{time}}`, `{{integer}}`, `{{float}}`, `{{hash}}`, `{{any}}`) that validate the format of dynamic values, not just their presence.

</div>

### Block Organization

No other implementation supports grouping related code blocks with shared state. DocTest's `group`, `setup`, and `teardown` attributes allow multi-block sequences where earlier blocks set up context for later ones.

### Framework Integration

Rust can use `extern crate` in doc tests. DocTest's `bootstrap` attribute allows loading any PHP framework (Laravel, Symfony, etc.) before execution.

