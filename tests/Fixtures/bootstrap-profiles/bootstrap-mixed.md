# Mixed Blocks

Block without bootstrap:

```php
echo "no bootstrap needed";
```
<!-- doctest: no bootstrap needed -->

Block with bootstrap:

```php bootstrap="math"
echo doctest_multiply(3, 4);
```
<!-- doctest: 12 -->

Another block without bootstrap:

```php
$x = 42;
echo $x;
```
<!-- doctest: 42 -->
