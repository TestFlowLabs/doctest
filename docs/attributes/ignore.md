# ignore

The `ignore` attribute tells DocTest to skip a code block entirely. The block is not executed, not syntax-checked, and is reported as "skipped" in the output.

## Syntax

````markdown
```php ignore
// This code will not be executed
$config = require 'missing-file.php';
```
````

## Console Output

Ignored blocks appear with the skip symbol:

```
  :5 ⊘ $config = require 'missing-file.php';  [1/3]
```

## When to Use

- Code that depends on external resources not available during testing
- Examples showing configuration that can't run standalone
- Pseudocode or incomplete snippets
- Blocks that intentionally demonstrate incorrect usage

## Examples

### External dependency

````markdown
```php ignore
// Requires a running Redis server
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
```
````

### Configuration example

````markdown
```php ignore
// This file doesn't exist in the test environment
return require __DIR__ . '/config/app.php';
```
````
