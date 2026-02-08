# Multiple HTML comments before a block

Multiple doctest-attr comments before a code block should throw an error.

<!-- doctest-attr: group="first" -->
<!-- doctest-attr: group="second" -->
```php
echo 'hello';
```
<!-- doctest: hello -->
