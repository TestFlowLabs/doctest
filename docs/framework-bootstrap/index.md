# Framework Bootstrap

If your documentation examples need access to a framework (Laravel, Symfony, etc.) or a custom autoloader, you can configure a bootstrap file that runs before every code block.

## Configuration

Add a `bootstrap` key to your `doctest.php` config:

```php ignore
return [
    'bootstrap' => 'tests/doctest-bootstrap.php',
];
```

The bootstrap file is `require_once`'d at the top of every generated script, giving your code blocks access to the framework environment.

## Examples

### Laravel

Create a bootstrap file that boots the Laravel application:

```php ignore
// tests/doctest-bootstrap.php
require_once __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
```

Your code blocks can then use facades, Eloquent, and the service container:

````markdown
```php
$users = DB::table('users')->get();
echo $users->count();
```
````

### Symfony

```php ignore
// tests/doctest-bootstrap.php
require_once __DIR__.'/../vendor/autoload.php';

$kernel = new \App\Kernel('test', true);
$kernel->boot();
```

### Custom Autoloader

For projects without a framework, just load the autoloader:

```php ignore
// tests/doctest-bootstrap.php
require_once __DIR__.'/../vendor/autoload.php';
```

## Tips

- Keep your bootstrap file minimal — it runs for every code block
- Use SQLite in-memory databases for documentation examples that need a database
- Group related database examples with the `group` attribute and use `setup`/`teardown` blocks for table creation and cleanup
