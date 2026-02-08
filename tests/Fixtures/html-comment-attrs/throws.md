# Throws via HTML comment

<!-- doctest-attr: throws(InvalidArgumentException, "Division by zero") -->
```php
function divide(int $a, int $b): float {
    if ($b === 0) {
        throw new InvalidArgumentException('Division by zero');
    }
    return $a / $b;
}

divide(10, 0);
```
