# Multiple HTML comments before a block

Only the first matching doctest-attr comment should be used.

<!-- doctest-attr: group="first" -->
<!-- doctest-attr: group="second" -->
```php
echo 'hello';
```
<!-- doctest: hello -->
