# no_run

The `no_run` attribute tells DocTest to check the syntax of a code block without executing it. The code is validated using `php -l` (lint).

## Syntax

````markdown
```php no_run
// Syntax is checked but code is not executed
$db->query('SELECT * FROM users');
```
````

## How It Works

1. The code is written to a temporary file
2. `php -l` runs against the file to check syntax
3. If the syntax is valid, the block passes
4. If there's a syntax error, the block fails with the error message

## When to Use

- Examples that require runtime resources (databases, APIs, services)
- Code showing patterns that can't run standalone
- Snippets that demonstrate syntax without needing execution

## Examples

### Database query

````markdown
```php no_run
$users = DB::table('users')
    ->where('active', true)
    ->orderBy('name')
    ->get();
```
````

### API call

````markdown
```php no_run
$response = $client->post('/api/users', [
    'json' => ['name' => 'Alice', 'email' => 'alice@example.com'],
]);
```
````

## Difference from ignore

| | `ignore` | `no_run` |
|---|---|---|
| Syntax check | No | Yes |
| Execution | No | No |
| Reports as | Skipped | Pass/Fail |
