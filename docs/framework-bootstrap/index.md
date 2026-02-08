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

## Bootstrap Profiles

When different code blocks need different environments — some need a framework, others need a database, others need neither — you can use **bootstrap profiles** instead of loading everything in a single global bootstrap file.

### Creating Profiles

Create a `.doctest/` directory in your project root and add PHP files. Each file becomes a profile named after its filename (without the `.php` extension):

```
.doctest/
├── laravel.php      → profile "laravel"
├── database.php     → profile "database"
└── helpers.php      → profile "helpers"
```

```php ignore
// .doctest/laravel.php
require_once __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
```

```php ignore
// .doctest/database.php
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

### Using Profiles

Add `bootstrap="profile-name"` to the code fence info string:

````markdown
```php bootstrap="laravel"
$users = User::all();
echo $users->count();
```
````

Blocks without a `bootstrap` attribute run without any profile — exactly as before.

### Composing Profiles

Combine multiple profiles with comma-separated names:

````markdown
```php bootstrap="laravel,database"
// Both Laravel and database environments are available
DB::connection()->getPdo();
```
````

Profiles are loaded left to right: `laravel.php` first, then `database.php`.

### Execution Order

When all layers are used, code runs in this order:

```
1. Global bootstrap (from config)
2. Bootstrap profiles (left to right)
3. Setup block (if in a group)
4. Code block
5. Teardown block (if in a group)
```

### Profiles with Groups

Bootstrap profiles work with grouped blocks. All blocks in the same group must use the same bootstrap profile:

````markdown
```php bootstrap="database" setup group="queries"
$pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY, name TEXT)');
$pdo->exec("INSERT INTO items (name) VALUES ('Widget')");
```

```php bootstrap="database" group="queries"
$stmt = $pdo->query('SELECT name FROM items');
echo $stmt->fetchColumn();
```
<!-- doctest: Widget -->

```php bootstrap="database" teardown group="queries"
$pdo->exec('DROP TABLE items');
```
````

### Example: Mixed Documentation

A single markdown file can have blocks with different bootstrap needs:

````markdown
## Pure PHP

```php
echo strtoupper('hello');
```
<!-- doctest: HELLO -->

## Framework Features

```php bootstrap="laravel"
echo config('app.name');
```
<!-- doctest: Laravel -->

## Database Queries

```php bootstrap="laravel,database"
$count = DB::table('users')->count();
echo $count;
```
<!-- doctest: 0 -->
````

## Tips

- Keep your bootstrap file minimal — it runs for every code block
- Use SQLite in-memory databases for documentation examples that need a database
- Group related database examples with the `group` attribute and use `setup`/`teardown` blocks for table creation and cleanup
- Use bootstrap profiles when different blocks need different environments
- Profile names must match filenames in `.doctest/` exactly (without `.php`)
- Only `.php` files at the top level of `.doctest/` are discovered as profiles
