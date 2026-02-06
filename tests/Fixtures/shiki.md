# Shiki Compatibility

Line highlighting:

```php{1,4-6}
$highlighted = true;
echo "works";
// Output: works
```

Diff markers:

```php
$before = 'old'; // [!code --]
$after = 'new';  // [!code ++]
echo $after;
// Output: new
```

Both combined:

```php{2-3}
$keep = true; // [!code ++]
$remove = false; // [!code --]
echo $keep ? 'yes' : 'no';
// Output: yes
```
