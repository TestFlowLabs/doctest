# JSON Reporter

The JSON reporter generates structured output for custom tooling, dashboards, or analysis scripts.

## Configuration

```php
return [
    'reporters' => [
        'json' => 'build/doctest.json',
    ],
];
```

## Output Format

```json
{
  "files": [
    {
      "file": "README.md",
      "blocks": [
        {
          "line": 3,
          "passed": true,
          "skipped": false,
          "duration": 0.02
        },
        {
          "line": 7,
          "passed": false,
          "skipped": false,
          "duration": 0.01,
          "error": "Output does not match",
          "expected": "Hello, World!",
          "actual": "Hello, World",
          "diff": "--- Expected\n+++ Actual\n..."
        }
      ]
    }
  ],
  "summary": {
    "total": 5,
    "passed": 3,
    "failed": 1,
    "skipped": 1
  }
}
```

## Structure

### `files`

Array of file results, each containing:

- **`file`** — Relative path to the markdown file
- **`blocks`** — Array of block results

### Block Fields

| Field | Type | Always Present | Description |
|-------|------|----------------|-------------|
| `line` | `int` | Yes | Start line of the code block |
| `passed` | `bool` | Yes | Whether the block passed |
| `skipped` | `bool` | Yes | Whether the block was skipped |
| `duration` | `float` | Yes | Execution time in seconds |
| `error` | `string` | On failure | Error message |
| `expected` | `string` | On failure | Expected output |
| `actual` | `string` | On failure | Actual output |
| `diff` | `string` | On failure | Unified diff |

### `summary`

Aggregate counts across all files.

## Use Cases

- Custom CI dashboard integration
- Trend analysis over time
- Automated documentation quality reports
- Integration with other tools via JSON parsing
