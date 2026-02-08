# HTML Comment Attribute Syntax

An alternative way to specify attributes using HTML comments instead of the code fence info string.

## Why?

Adding attributes to the code fence info string (e.g., `` ```php ignore ``) can break syntax highlighting in editors like PHPStorm, VS Code, and others. These editors only recognize the language identifier (`php`) and treat anything else in the info string as unknown, disabling PHP-specific highlighting for the entire block.

HTML comment attributes solve this by moving attributes **outside** the code fence:

````markdown
<!-- doctest-attr: ignore -->
```php
// Editor sees plain ```php — full syntax highlighting preserved
echo 'Hello, World!';
```
````

The rendered documentation looks identical — HTML comments are invisible to readers.

## Syntax

````markdown
<!-- doctest-attr: attribute1 key="value" attribute2 -->
```php
// your code here
```
````

The comment must appear on the line **immediately before** the code fence. If any other content (paragraphs, headings, etc.) appears between the comment and the fence, the comment is ignored.

## Supported Attributes

| Attribute | HTML Comment Syntax |
|-----------|-------------------|
| ignore | `<!-- doctest-attr: ignore -->` |
| no_run | `<!-- doctest-attr: no_run -->` |
| throws | `<!-- doctest-attr: throws -->` |
| throws (class) | `<!-- doctest-attr: throws(RuntimeException) -->` |
| throws (class + message) | `<!-- doctest-attr: throws(RuntimeException, "not found") -->` |
| parse_error | `<!-- doctest-attr: parse_error -->` |
| group | `<!-- doctest-attr: group="name" -->` |
| setup | `<!-- doctest-attr: setup group="name" -->` |
| teardown | `<!-- doctest-attr: teardown group="name" -->` |
| bootstrap | `<!-- doctest-attr: bootstrap="laravel" -->` |
| bootstrap (multiple) | `<!-- doctest-attr: bootstrap="laravel,database" -->` |

Multiple attributes can be combined in a single comment:

````markdown
<!-- doctest-attr: setup group="db" bootstrap="laravel" -->
```php
$pdo = new PDO('sqlite::memory:');
```
````

## Examples

### Ignore

````markdown
<!-- doctest-attr: ignore -->
```php
echo $config['key']; // Can't run standalone
```
````

### No Run (syntax check only)

````markdown
<!-- doctest-attr: no_run -->
```php
$users = DB::table('users')->where('active', true)->get();
```
````

### Throws

````markdown
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
````

### Group with setup

````markdown
<!-- doctest-attr: setup group="cart" -->
```php
$cart = [];
```

<!-- doctest-attr: group="cart" -->
```php
$cart[] = ['item' => 'Widget', 'price' => 9.99];
echo count($cart);
```
<!-- doctest: 1 -->
````

### Bootstrap

````markdown
<!-- doctest-attr: bootstrap="laravel" -->
```php
$user = User::factory()->create();
echo $user->exists;
```
<!-- doctest: 1 -->
````

## Merging Rules

Both syntaxes can coexist in the same file — some blocks can use info string attributes, others can use HTML comment attributes.

However, a single block **cannot** have the same attribute from both sources. If both the info string and the HTML comment set the same attribute (e.g., both set `group`), DocTest throws a `RuntimeException`:

```
Conflicting group: info string has 'cart' but HTML comment has 'orders'. Use only one source.
```

Complementary attributes are fine — for example, `group` from the comment and `setup` from the info string:

````markdown
<!-- doctest-attr: group="db" -->
```php setup
$pdo = new PDO('sqlite::memory:');
```
````
