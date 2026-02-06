# throws

The `throws` attribute tells DocTest to expect an exception from the code block. The block passes if the specified exception (or any `Throwable`) is thrown.

## Syntax

### Expect any exception

```php throws
throw new RuntimeException('Something went wrong');
```

### Expect a specific exception class

```php throws(InvalidArgumentException)
throw new InvalidArgumentException('Expected an integer');
```

### Expect a specific exception with message

```php throws(InvalidArgumentException, "Expected an integer")
throw new InvalidArgumentException('Expected an integer');
```

## How It Works

1. The code is wrapped in a `try/catch` block
2. If an exception is thrown, its class and message are captured
3. If a specific class was expected, the thrown class is compared
4. If a message was specified, a `str_contains()` check is performed
5. If no exception is thrown, the block fails

## Failure Conditions

The block fails when:

- No exception is thrown
- A different exception class is thrown than expected
- The exception message doesn't contain the expected substring

## Examples

### Division by zero

```php throws(DivisionByZeroError)
$result = 1 / 0;
```

### Custom exception

````markdown
```php throws(App\Exceptions\ValidationException)
validate(['email' => 'not-an-email'], ['email' => 'email']);
```
````

### Exception with message check

```php throws(RuntimeException, "not found")
throw new RuntimeException('File not found');
```
