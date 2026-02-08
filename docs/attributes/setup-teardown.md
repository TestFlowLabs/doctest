# setup / teardown

The `setup` and `teardown` attributes define code that runs before and after group blocks respectively.

## Syntax

````markdown
```php setup group="name"
// Runs before group blocks
```

```php teardown group="name"
// Runs after group blocks
```
````

## How It Works

### Setup

- Runs **before** any regular blocks in the group
- Typically initializes resources (database connections, test data)
- Variables defined in setup are available to all group blocks

### Teardown

- Runs **after** all regular blocks in the group
- Typically cleans up resources (drop tables, close connections)
- Has access to all variables from setup and group blocks

## Example

````markdown
```php setup group="users"
$pdo = new PDO('sqlite::memory:');
$pdo->exec('CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    name TEXT,
    email TEXT
)');
```

```php group="users"
$stmt = $pdo->prepare('INSERT INTO users (name, email) VALUES (?, ?)');
$stmt->execute(['Alice', 'alice@example.com']);
echo $pdo->lastInsertId();
```
<!-- doctest: 1 -->

```php group="users"
$user = $pdo->query("SELECT name FROM users WHERE id = 1")->fetch();
echo $user['name'];
```
<!-- doctest: Alice -->

```php teardown group="users"
$pdo->exec('DROP TABLE users');
$pdo = null;
```
````

## Global Setup/Teardown

Setup and teardown blocks without a group apply to **all** non-grouped blocks in the file:

````markdown
```php setup
// This runs before every non-grouped block
require_once 'vendor/autoload.php';
```

```php
echo class_exists('Some\Class') ? 'yes' : 'no';
```
<!-- doctest: yes -->

```php teardown
// This runs after every non-grouped block
```
````

## Multiple Setup/Teardown

You can have multiple setup or teardown blocks. They execute in document order:

````markdown
```php setup group="app"
// First setup: load dependencies
require_once 'vendor/autoload.php';
```

```php setup group="app"
// Second setup: configure
$config = ['debug' => true];
```
````

## Tips

- Setup blocks don't need assertions — they're purely for initialization
- Teardown blocks are optional but recommended for resource cleanup
- If a setup block fails, all blocks in the group fail
