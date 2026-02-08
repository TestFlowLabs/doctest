# Setup and teardown via HTML comment

<!-- doctest-attr: setup group="db" -->
```php
$pdo = new PDO('sqlite::memory:');
$pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY, name TEXT)');
```

<!-- doctest-attr: group="db" -->
```php
$pdo->exec("INSERT INTO items (name) VALUES ('Widget')");
$count = $pdo->query('SELECT COUNT(*) FROM items')->fetchColumn();
echo $count;
```
<!-- doctest: 1 -->

<!-- doctest-attr: teardown group="db" -->
```php
$pdo->exec('DROP TABLE items');
```
