# bootstrap

The `bootstrap` attribute loads PHP files before executing a code block. Use this for framework initialization, autoloader setup, or environment configuration.

## Syntax

### Info String

````markdown
```php bootstrap="laravel"
$user = User::factory()->create();
echo $user->exists;
```
<!-- doctest: 1 -->
````

### HTML Comment

````markdown
<!-- doctest-attr: bootstrap="laravel" -->
```php
$user = User::factory()->create();
echo $user->exists;
```
<!-- doctest: 1 -->
````

### Multiple Profiles

````markdown
```php bootstrap="laravel,database"
// Both laravel.php and database.php are loaded
```
````

## How It Works

1. Bootstrap profiles are PHP files stored in the `.doctest/` directory
2. Each file name (without `.php`) becomes a profile name
3. When a block uses `bootstrap="name"`, DocTest `require_once`'s that file before executing the block
4. If a global bootstrap is set in the config, it loads first, then per-block profiles

## Profile Discovery

DocTest discovers all `.php` files in the bootstraps directory:

```
.doctest/
  ├── laravel.php      → bootstrap="laravel"
  ├── database.php     → bootstrap="database"
  └── redis.php        → bootstrap="redis"
```

The directory defaults to `.doctest/` in the project root. Override it with the `bootstraps_dir` config option.

## Example: Laravel Bootstrap

**`.doctest/laravel.php`:**

```php ignore
<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
```

**Documentation using the profile:**

````markdown
<!-- doctest-attr: bootstrap="laravel" -->
```php
$user = User::factory()->create(['name' => 'Alice']);
echo $user->name;
```
<!-- doctest: Alice -->
````

## Global vs. Block Bootstrap

| Type | Source | Scope | Use Case |
|------|--------|-------|----------|
| Global | `bootstrap` config option | All blocks | Composer autoloader |
| Block | `bootstrap` attribute | Single block | Framework, database |

Global bootstrap runs before every block. Block bootstrap runs only for blocks with the attribute. When both are set, global loads first, then per-block profiles.

## Error Handling

If a profile name doesn't match any file in the bootstraps directory:

```
Unknown bootstrap profile "typo". Available profiles: database, laravel, redis
```

## Combining with Other Attributes

Bootstrap works with other attributes:

````markdown
<!-- doctest-attr: bootstrap="laravel" group="users" setup -->
```php
$pdo = DB::connection()->getPdo();
```
````

See [HTML Comment Syntax](/attributes/html-comment-syntax) for the full combining rules.
