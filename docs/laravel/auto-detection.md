# Auto-Detection

DocTest automatically detects Laravel projects by checking for the presence of `bootstrap/app.php` in the project root.

## Detection Logic

```
Project root
├── bootstrap/
│   └── app.php    ← DocTest checks for this file
├── vendor/
│   └── autoload.php
└── ...
```

If `bootstrap/app.php` exists, DocTest considers it a Laravel project and enables Laravel-specific features.

## What Auto-Detection Enables

When a Laravel project is detected:

1. **Autoloader** — `vendor/autoload.php` is required
2. **Application bootstrap** — `bootstrap/app.php` is loaded
3. **Kernel bootstrap** — The console kernel is bootstrapped, loading service providers and configuration

## Generated Bootstrap Code

DocTest generates the following bootstrap code for each code block:

```php ignore
require_once '/path/to/vendor/autoload.php';
$app = require_once '/path/to/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
```

This gives your documentation examples access to the full Laravel environment.

## Disabling Auto-Detection

If you're in a Laravel project but don't want the framework bootstrapped for your doc tests, use the standalone binary without Laravel bootstrap:

```bash
vendor/bin/doctest
```

The Artisan command always bootstraps Laravel.
