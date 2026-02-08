# Bootstrap with Setup and Teardown

```php setup group="calc"
$multiplier = 10;
```

```php teardown group="calc"
unset($multiplier);
```

```php bootstrap="math" group="calc"
echo doctest_add(5, 5) * $multiplier;
```
<!-- doctest: 100 -->
