# Basic Test

Simple PHP blocks with Output assertions.

```php
echo "Hello, World!";
```
<!-- doctest: Hello, World! -->

Some text between blocks.

```php
$x = 42;
echo $x;
```
<!-- doctest: 42 -->

Block with no assertions (smoke test):

```php
$y = 100;
```

Multi-line output:

```php
echo "line1\n";
echo "line2\n";
echo "line3";
```
<!-- doctest:
line1
line2
line3
-->
