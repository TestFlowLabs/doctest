# Output Comparison

DocTest normalizes and compares output using a multi-step pipeline. This page covers the details.

## Comparison Pipeline

```
Expected → Normalize → Wildcard check? → Compare → Result
Actual   → Normalize ─────────────────↗
```

### 1. Normalization

Both expected and actual output go through the `Normalizer`:

- Trailing whitespace on each line is trimmed
- Line endings are unified to `\n`
- Trailing newlines are removed

This prevents false failures from invisible whitespace differences.

### 2. Wildcard Detection

<div v-pre>

If the expected output contains any `{{...}}` patterns, the comparison switches to regex mode.

</div>

### 3. Comparison

**Without wildcards:** Simple string equality (`===`)

**With wildcards:**
1. The expected string is escaped with `preg_quote()`
2. Each wildcard placeholder is replaced with its regex pattern
3. The result is wrapped in `^...$` anchors with the `s` (dotall) flag
4. `preg_match()` tests the actual output against the pattern

### 4. Diff Generation

When comparison fails, a unified diff is generated showing the differences:

```diff
--- Expected
+++ Actual
@@ @@
-Hello, World!
+Hello, World
```

## Wildcard Matching Details

The `WildcardMatcher` processes patterns in this order:

<div v-pre>

| Wildcard | Regex | Notes |
|----------|-------|-------|
| `{{any}}` | `.+?` | Non-greedy, doesn't match newlines |
| `{{int}}` | `-?\d+` | Optional negative sign |
| `{{float}}` | `-?\d+\.?\d*` | Matches integers too |
| `{{uuid}}` | `[0-9a-f]{8}-...-[0-9a-f]{12}` | Lowercase hex |
| `{{datetime}}` | `\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[^\s]*` | ISO 8601 with optional timezone |
| `{{date}}` | `\d{4}-\d{2}-\d{2}` | ISO date only |
| `{{time}}` | `\d{2}:\d{2}:\d{2}` | 24-hour time |
| `{{...}}` | `[\s\S]*?` | Non-greedy, spans newlines |

Order matters: `{{datetime}}` is checked before `{{date}}` and `{{time}}` to avoid partial matches.

</div>

## JSON Comparison

The `OutputJson` assertion uses a different comparison path:

1. Both expected and actual strings are decoded with `json_decode()`
2. If either fails to decode, the assertion fails with a descriptive error
3. The decoded PHP values are compared with `===`

This means:
- Key order in objects doesn't matter
- Array order **does** matter
- Type matters (`1` vs `"1"`)

## Configuration

Output comparison behavior can be configured:

```php ignore
return [
    'output' => [
        'normalize_whitespace' => true,  // Normalize whitespace (default: true)
        'trim_trailing'        => true,  // Trim trailing whitespace (default: true)
    ],
];
```
