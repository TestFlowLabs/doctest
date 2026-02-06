# Mixed Languages

PHP block:

```php
echo "PHP works";
```
<!-- doctest: PHP works -->

JavaScript block (should be skipped):

```javascript
console.log("JS");
```

Python block (should be skipped):

```python
print("Python")
```

Shell block (should be skipped):

```bash
echo "shell"
```

Another PHP block:

```php
echo "second PHP";
```
<!-- doctest: second PHP -->

Block without language (should be skipped):

```
plain text
```
