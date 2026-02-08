# Text between comment and block

The doctest-attr comment is NOT immediately before the code block.

<!-- doctest-attr: ignore -->

Some paragraph text separating them.

```php
echo 'This should NOT be ignored because text broke the adjacency';
```
<!-- doctest: This should NOT be ignored because text broke the adjacency -->
