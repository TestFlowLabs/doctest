# Conflicting attributes

Both info string and HTML comment set group — should throw.

<!-- doctest-attr: group="from-comment" -->
```php group="from-info"
echo 'conflict';
```
