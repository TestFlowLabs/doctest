# Expect Assertion

The `Expect` assertion evaluates a **PHP expression** that must be truthy.

## Syntax

### Inline Comment

```php
$result = array_sum([1, 2, 3, 4, 5]);
// Expect: $result === 15
```

### HTML Comment

````markdown
```php
$result = array_sum([1, 2, 3, 4, 5]);
```
<!-- doctest-expect: $result === 15 -->
````

## How It Works

1. The code block executes
2. After the code runs, the expression is evaluated with `(bool)(expression)`
3. If the expression evaluates to `true`, the assertion passes
4. If it evaluates to `false`, the assertion fails with the expression shown in the error

## Expression Scope

The expression has access to all variables defined in the code block:

```php
$items = [1, 2, 3];
$count = count($items);
$sum = array_sum($items);
// Expect: $count === 3
// Expect: $sum === 6
```

## Multiple Expects

You can have multiple `Expect` assertions on the same block:

```php
$name = 'DocTest';
$version = 1;
// Expect: is_string($name)
// Expect: $version >= 1
// Expect: strlen($name) > 0
```

## When to Use

- When you need to verify a computed value without printing it
- When the assertion involves a comparison or function call
- When documenting APIs where return values matter more than printed output

## Tips

- The expression must be a valid PHP expression
- Use strict comparisons (`===`) when possible
- You can call functions in the expression: `// Expect: is_array($result)`
