# Bootstrap & Database

DocTest provides built-in support for bootstrapping the Laravel application and setting up a test database for documentation examples.

## Application Bootstrap

When the Laravel bootstrap is active, your code blocks have access to:

- **Facades** — `DB::`, `Cache::`, `Config::`, `Route::`, etc.
- **Eloquent** — Models, relationships, queries
- **Service container** — `app()`, dependency injection
- **Helpers** — `config()`, `env()`, `storage_path()`, etc.

## Database Setup

The `DatabaseSetup` class configures an in-memory SQLite database for documentation examples:

### Configuration

| Option | Default | Description |
|--------|---------|-------------|
| `driver` | `sqlite` | Database driver |
| `database` | `:memory:` | Database path |
| `runMigrations` | `false` | Run `migrate:fresh` on setup |
| `connection` | `doctest` | Connection name |

### What It Does

**Setup:**

1. Registers a `doctest` database connection with SQLite in-memory
2. Sets it as the default connection
3. Optionally runs `migrate:fresh`

**Teardown:**

1. Disconnects the `doctest` connection

### Example with Database

Using groups with database setup:

````markdown
```php setup group="users"
// Database is available via Laravel's DB facade
DB::statement('CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL
)');
```

```php group="users"
DB::table('users')->insert([
    'name' => 'Alice',
    'email' => 'alice@example.com',
]);

$count = DB::table('users')->count();
echo $count;
```
<!-- doctest: 1 -->

```php teardown group="users"
DB::statement('DROP TABLE users');
```
````

## Service Providers

DocTest can register additional service providers if needed:

```php
// In your setup block
$app->register(\App\Providers\CustomProvider::class);
```

## Tips

- Use SQLite in-memory for fast, isolated database tests
- Group related database examples with the `group` attribute
- Always include a `teardown` block to clean up tables
- The `doctest` connection is separate from your application's default connection
