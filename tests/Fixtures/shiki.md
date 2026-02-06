# Shiki Compatibility

Line highlighting:

```php{1,4-6}
$highlighted = true;
echo "works";
```
<!-- doctest: works -->

Diff markers:

```php
$before = 'old'; // [!code --]
$after = 'new';  // [!code ++]
echo $after;
```
<!-- doctest: new -->

Both combined:

```php{2-3}
$keep = true; // [!code ++]
$remove = false; // [!code --]
echo $keep ? 'yes' : 'no';
```
<!-- doctest: yes -->
