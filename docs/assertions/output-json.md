# OutputJson Assertion

The `OutputJson` assertion compares JSON output **structurally**, ignoring key order and whitespace.

## Syntax

````markdown
```php
echo json_encode(['name' => 'DocTest', 'php' => '8.4+']);
```
<!-- doctest-json: {"name": "DocTest", "php": "8.4+"} -->
````

## How It Works

1. The code block executes and its output is captured
2. Both the expected JSON and actual output are decoded with `json_decode()`
3. The decoded structures are compared with `===`
4. If either side has invalid JSON, the assertion fails with a descriptive error

## Key Order Independence

The comparison is structural, so key order doesn't matter:

```php
echo json_encode(['b' => 2, 'a' => 1]);
```
<!-- doctest-json: {"b": 2, "a": 1} -->

Both produce the same decoded structure, so this passes.

## Nested Structures

Works with nested objects and arrays:

```php
echo json_encode([
    'user' => ['name' => 'Alice', 'age' => 30],
    'roles' => ['admin', 'editor'],
]);
```
<!-- doctest-json: {"user": {"name": "Alice", "age": 30}, "roles": ["admin", "editor"]} -->

## When to Use

- When your code outputs JSON and you want to verify the structure
- When key ordering may vary between PHP versions or configurations
- When you want to compare nested data structures

::: warning
Array order **does** matter. `["a", "b"]` is not equal to `["b", "a"]`.
:::
