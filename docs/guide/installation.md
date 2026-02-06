# Installation

## Requirements

- **PHP 8.4** or higher

## Composer

Install DocTest as a development dependency:

```bash
composer require --dev testflowlabs/doctest
```

After installation, the `doctest` binary is available at:

```bash
vendor/bin/doctest
```

## Verify Installation

Run without arguments to test the default paths (`docs/` and `README.md`):

```bash
vendor/bin/doctest
```

If no markdown files contain PHP code blocks, DocTest will exit cleanly with no output.

## Laravel

If you're using Laravel, DocTest auto-detects your application and registers an Artisan command:

```bash
php artisan doctest
```

See [Laravel Integration](/laravel/) for details.

## Configuration

Create an optional `doctest.php` in your project root to customize paths, timeouts, and reporters:

```bash
touch doctest.php
```

See [Configuration](/configuration/) for the full reference.
