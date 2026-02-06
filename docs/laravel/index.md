# Laravel Integration

DocTest integrates with Laravel automatically. When installed in a Laravel project, it detects the framework and provides additional features.

## Features

- **Artisan command** — Run `php artisan doctest` instead of `vendor/bin/doctest`
- **Auto-detection** — Automatically detects Laravel projects via `bootstrap/app.php`
- **Bootstrap** — Optionally bootstraps the Laravel application for your code blocks
- **Database setup** — Built-in SQLite in-memory database configuration for doc tests

## Quick Start

```bash
# Install
composer require --dev testflowlabs/doctest

# Run via Artisan
php artisan doctest

# Or via the standard binary
vendor/bin/doctest
```

## When to Use Laravel Integration

The Laravel integration is useful when your documentation examples need:

- Access to Laravel facades (`DB::`, `Cache::`, `Config::`, etc.)
- Eloquent models and database queries
- Service container bindings
- Application configuration

For documentation that only uses plain PHP, the standard `vendor/bin/doctest` works without any Laravel-specific setup.
