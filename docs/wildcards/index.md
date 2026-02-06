# Wildcards

Wildcards let you match dynamic output without hardcoding values. Use them in any output-based assertion when the exact value changes between runs.

## Available Wildcards

<div v-pre>

| Wildcard | Matches | Regex Pattern |
|----------|---------|---------------|
| `{{any}}` | Any non-empty content (non-greedy) | `.+?` |
| `{{int}}` | Integer (optional negative sign) | `-?\d+` |
| `{{float}}` | Float or integer | `-?\d+\.?\d*` |
| `{{uuid}}` | UUID v4 format | `[0-9a-f]{8}-[0-9a-f]{4}-...` |
| `{{date}}` | ISO date (`YYYY-MM-DD`) | `\d{4}-\d{2}-\d{2}` |
| `{{time}}` | Time (`HH:MM:SS`) | `\d{2}:\d{2}:\d{2}` |
| `{{datetime}}` | ISO datetime | `\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}...` |
| `{{...}}` | Any content including newlines (non-greedy) | `[\s\S]*?` |

</div>

## Usage

Wildcards work in HTML comment assertions:

````markdown
```php
echo 'Request took 42ms at ' . date('Y-m-d');
```
<!-- doctest: Request took {{int}}ms at {{date}} -->
````

## Examples

### Timestamps

````markdown
```php
echo 'Generated: ' . date('Y-m-d H:i:s');
```
<!-- doctest: Generated: {{date}} {{time}} -->
````

### UUIDs

````markdown
```php
echo sprintf('User ID: %s', '550e8400-e29b-41d4-a716-446655440000');
```
<!-- doctest: User ID: {{uuid}} -->
````

### Mixed dynamic content

````markdown
```php
echo json_encode([
    'id' => 42,
    'created' => '2024-01-15T10:30:00Z',
    'price' => 19.99,
]);
```
<!-- doctest: {"id":{{int}},"created":"{{datetime}}","price":{{float}}} -->
````

<div v-pre>

### Multi-line with `{{...}}`

The `{{...}}` wildcard spans across newlines, useful for matching variable-length content:

</div>

````markdown
```php
echo "Header\nSome variable content\nhere\nFooter";
```
<!-- doctest: Header{{...}}Footer -->
````

## How It Works

<div v-pre>

1. DocTest checks if the expected output contains any `{{...}}` patterns
2. The expected string is escaped for regex
3. Each wildcard placeholder is replaced with its regex pattern
4. The resulting regex is matched against the actual output with `preg_match()`

</div>

## Combining Wildcards

You can use multiple wildcards in a single assertion:

````markdown
```php
echo sprintf('[%s] %s: Processed %d items in %.2fs',
    date('Y-m-d'),
    'INFO',
    150,
    0.42
);
```
<!-- doctest: [{{date}}] {{any}}: Processed {{int}} items in {{float}}s -->
````

## Tips

<div v-pre>

- `{{any}}` is non-greedy — it matches the shortest possible string
- `{{...}}` is also non-greedy but can match newlines, making it useful for skipping variable blocks
- `{{int}}` matches optional negative signs: `-42` matches `{{int}}`
- `{{float}}` also matches integers: `42` matches `{{float}}`
- Wildcards are case-sensitive: `{{INT}}` is not recognized

</div>
