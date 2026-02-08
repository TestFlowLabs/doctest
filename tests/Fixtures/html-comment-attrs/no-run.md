# No run via HTML comment

<!-- doctest-attr: no_run -->
```php
$db = new PDO('sqlite::memory:');
$db->query('SELECT 1');
```
