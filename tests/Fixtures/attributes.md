# Attributes Test

Ignore attribute:

```php ignore
echo "this is ignored";
```

No-run attribute:

```php no_run
$validSyntax = true;
```

Throws attribute:

```php throws
throw new RuntimeException("test");
```

Throws with class:

```php throws(InvalidArgumentException)
throw new InvalidArgumentException("bad input");
```

Throws with class and message:

```php throws(InvalidArgumentException, "bad input")
throw new InvalidArgumentException("bad input provided");
```

Parse error attribute:

```php parse_error
$x = {invalid syntax;
```

Setup block:

```php setup
$testDir = sys_get_temp_dir() . '/doctest-test';
mkdir($testDir);
```

Teardown block:

```php teardown
rmdir($testDir);
```

Group attribute:

```php group="order-flow"
$state = 'pending';
echo $state;
// Output: pending
```

```php group="order-flow"
$state = 'paid';
echo $state;
// Output: paid
```
