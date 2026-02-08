# Framework Bootstrap

If your documentation examples need access to a framework (Laravel, Symfony, etc.) or a custom autoloader, you can configure a bootstrap file that runs before every code block.

## Configuration

Add a `bootstrap` key to your `doctest.php` config:

```php ignore
return [
    'bootstrap' => '.doctest/bootstrap.php',
];
```

The bootstrap file is `require_once`'d at the top of every generated script, giving your code blocks access to the framework environment.

## Examples

### Laravel

Create a bootstrap file that boots the Laravel application:

```php ignore
// .doctest/bootstrap.php
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
// .doctest/bootstrap.php
require_once __DIR__.'/../vendor/autoload.php';

$kernel = new \App\Kernel('test', true);
$kernel->boot();
```

### Laravel Packages (Orchestra Testbench)

Laravel packages don't have a full application — they need [Orchestra Testbench](https://packages.tools/testbench) to create one. A bootstrap profile can set up the application, register service providers, configure the database, and run migrations:

```php ignore
// .doctest/laravel.php
require_once __DIR__.'/../vendor/autoload.php';

$app = \Orchestra\Testbench\Foundation\Application::create(
    options: [
        'extra' => [
            'providers' => [
                \YourVendor\YourPackage\YourServiceProvider::class,
                // Add any dependency service providers
            ],
        ],
    ],
);
```

**Setting config values** — use `config()->set()` after the application boots:

```php ignore
// .doctest/database.php
config()->set('database.default', 'testing');
config()->set('database.connections.testing', [
    'driver'   => 'sqlite',
    'database' => ':memory:',
]);
config()->set('cache.default', 'array');
```

**Running migrations** — include migration stubs directly from your package's `database/migrations/` directory:

```php ignore
// .doctest/migrations.php
$migration = include __DIR__.'/../database/migrations/create_your_table.php.stub';
$migration->up();
```

Then compose profiles as needed:

````markdown
```php bootstrap="laravel"
// Only needs the framework — no database
echo config('app.name');
```

```php bootstrap="laravel,database,migrations"
// Needs framework + database + tables
$count = DB::table('your_table')->count();
echo $count;
```
<!-- doctest: 0 -->
````

::: tip
Keep each concern in a separate profile. Blocks that only need the framework skip the database overhead, and blocks that need the database get it by composing profiles.
:::

### Custom Autoloader

For projects without a framework, just load the autoloader:

```php ignore
// .doctest/bootstrap.php
require_once __DIR__.'/../vendor/autoload.php';
```

## Bootstrap Profiles

When different code blocks need different environments — some need a framework, others need a database, others need neither — you can use **bootstrap profiles** instead of loading everything in a single global bootstrap file.

### Creating Profiles

Create a `.doctest/` directory in your project root and add PHP files. Each `.php` file at the top level becomes a profile:

```
.doctest/
├── laravel.php      → profile "laravel"   ✓
├── database.php     → profile "database"  ✓
├── helpers.php      → profile "helpers"   ✓
├── README.md        → ignored (not .php)  ✗
└── nested/
    └── special.php  → ignored (subdir)    ✗
```

Only `.php` files directly in `.doctest/` are discovered — subdirectories and non-PHP files are ignored.

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

When a code block runs, DocTest generates a single PHP file with all layers in shared scope:

```php ignore
<?php
// 1. Global bootstrap (from config, if set)
require_once '/path/to/bootstrap.php';

// 2. Bootstrap profiles (left to right, if specified)
require_once '/path/to/.doctest/laravel.php';
require_once '/path/to/.doctest/database.php';

// 3. Setup code (if block is in a group)
$pdo = new PDO('sqlite::memory:');

// 4. Your code block
echo $result;

// 5. Teardown code (if block is in a group)
$pdo = null;
```

All layers execute in the same process with shared variable scope.

### Profiles with Groups

Bootstrap profiles work with grouped blocks. **All blocks in the same group must use identical bootstrap profiles** — mixing different profiles within a group causes a runtime error:

```
RuntimeException: All blocks in group "queries" must have identical bootstrap profiles.
```

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

### Custom Profile Directory

The default profile directory is `.doctest/`. To use a different path, set `bootstraps_dir` in your `doctest.php` config:

```php ignore
return [
    'bootstraps_dir' => 'tests/.doctest',
];
```

## Tips

- Keep your bootstrap file minimal — it runs for every code block
- Use SQLite in-memory databases for documentation examples that need a database
- Group related database examples with the `group` attribute and use `setup`/`teardown` blocks for table creation and cleanup
- Use bootstrap profiles when different blocks need different environments
- Profile names must match filenames in `.doctest/` exactly (without `.php`)
