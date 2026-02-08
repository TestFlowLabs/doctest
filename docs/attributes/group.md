# group

The `group` attribute links code blocks together so they share state within a single PHP process. Variables defined in one block are accessible in subsequent blocks of the same group.

## Syntax

````markdown
```php group="name"
// Blocks with the same group name share state
```
````

## How It Works

1. All blocks with the same `group` value are collected
2. They execute in document order within a **single PHP process**
3. Variables, functions, and classes defined in earlier blocks are available in later ones
4. Each block's assertions are evaluated independently

## Example

````markdown
```php group="math"
$numbers = [1, 2, 3, 4, 5];
$sum = array_sum($numbers);
echo $sum;
```
<!-- doctest: 15 -->

```php group="math"
// $numbers and $sum are still available
$average = $sum / count($numbers);
echo $average;
```
<!-- doctest: 3 -->
````

## With Setup and Teardown

Groups are commonly used with [`setup`](/attributes/setup-teardown) and [`teardown`](/attributes/setup-teardown) blocks:

````markdown
```php setup group="database"
$pdo = new PDO('sqlite::memory:');
$pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
```

```php group="database"
$pdo->exec("INSERT INTO users (name) VALUES ('Alice')");
$count = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
echo $count;
```
<!-- doctest: 1 -->

```php group="database"
$pdo->exec("INSERT INTO users (name) VALUES ('Bob')");
$count = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
echo $count;
```
<!-- doctest: 2 -->

```php teardown group="database"
$pdo->exec('DROP TABLE users');
```
````

## Execution Order

Within a group:

1. `setup` blocks run first (in document order)
2. Regular group blocks run in document order
3. `teardown` blocks run last (in document order)

## Multiple Groups

You can have multiple independent groups in the same file:

````markdown
```php group="strings"
$greeting = 'Hello';
```

```php group="numbers"
$count = 42;
```

```php group="strings"
echo $greeting . ', World!';
```
<!-- doctest: Hello, World! -->

```php group="numbers"
echo $count * 2;
```
<!-- doctest: 84 -->
````

Each group runs in its own process.

## Alternative: HTML Comment Syntax

````markdown
<!-- doctest-attr: group="math" -->
```php
$sum = array_sum([1, 2, 3]);
echo $sum;
```
<!-- doctest: 6 -->
````

See [HTML Comment Syntax](/attributes/html-comment-syntax) for details.
