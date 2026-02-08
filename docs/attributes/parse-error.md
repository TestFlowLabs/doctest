# parse_error

The `parse_error` attribute tells DocTest to expect a PHP parse error. The block passes if the code fails to parse (non-zero exit code from `php -l`).

## Syntax

````markdown
```php parse_error
// This code has intentional syntax errors
echo 'Hello
```
````

## How It Works

1. The code is written to a temporary file as-is
2. The file is executed with PHP
3. A non-zero exit code (indicating a parse error) means the block **passes**
4. If the code runs successfully, the block **fails**

## When to Use

- Demonstrating what **invalid** PHP syntax looks like
- Teaching materials showing common mistakes
- Documentation that explains parser behavior

## Examples

### Missing semicolon

````markdown
```php parse_error
$x = 42
echo $x;
```
````

### Unclosed string

````markdown
```php parse_error
echo "Hello, World!;
```
````

### Invalid syntax

````markdown
```php parse_error
function 123invalid() {}
```
````

## Failure Condition

The block fails if the code executes successfully without a parse error:

````markdown
```php parse_error
// This is valid PHP — the block will FAIL
echo 'Hello';
```
````

## Alternative: HTML Comment Syntax

````markdown
<!-- doctest-attr: parse_error -->
```php
$x = 42
echo $x;
```
````

See [HTML Comment Syntax](/attributes/html-comment-syntax) for details.
