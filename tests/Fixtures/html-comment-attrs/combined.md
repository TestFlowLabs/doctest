# Combined: HTML comment attrs + info string attrs

Some blocks use HTML comment, others use info string — both in same file.

<!-- doctest-attr: ignore -->
```php
echo 'ignored via comment';
```

```php no_run
$db->query('SELECT 1');
```

<!-- doctest-attr: group="mix" -->
```php
$val = 100;
```

```php group="mix"
echo $val;
```
<!-- doctest: 100 -->
