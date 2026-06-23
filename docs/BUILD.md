# Building the Distribution Package

## Overview

This repository is the **canonical source tree** for the IMedia Registration
WordPress plugin. The production-ready plugin package is built from this tree
by a script and is **not** committed to version control.

## Prerequisites

- PHP 8.1+
- [Composer](https://getcomposer.org/)
- rsync

## Quick Start

```bash
# 1. Install all dependencies (including dev, for testing)
composer install

# 2. Run unit tests
composer test:unit

# 3. Build the distribution package
./tools/build-dist.sh --no-dev
```

Output is written to `wp-registration-plugin/wp-content/plugins/imedia-registration/`.

## Build Options

### `./tools/build-dist.sh`

Plain build — copies source and runs `composer install` (includes dev
dependencies). Use for local testing of the distribution layout.

### `./tools/build-dist.sh --no-dev`

Production build — copies source and runs `composer install --no-dev`.
Excludes phpunit, phpcs, and other development-only dependencies. Use for
deployment or release packaging.

### `./tools/build-dist.sh --help`

Prints usage information.

## Distribution Layout

```
wp-registration-plugin/
└── wp-content/
    └── plugins/
        └── imedia-registration/
            ├── imedia-registration.php   (plugin entry)
            ├── app/                      (PSR-4 application)
            ├── includes/                 (legacy WordPress hooks)
            ├── public/                   (web-accessible assets)
            ├── resources/                (views, JS, CSS)
            ├── config/                   (config.example.php)
            ├── cron/                     (scheduled tasks)
            ├── database/                 (schema + migrations)
            ├── routes.php                (HTTP routes)
            ├── uninstall.php
            └── vendor/                   (runtime Composer deps)
```

## What Gets Excluded

The build script explicitly excludes:

| Path | Reason |
|---|---|
| `vendor/` | Rebuilt by `composer install` in the distribution tree |
| `tests/` | Not shipped to production |
| `tools/` | Development/build utilities |
| `coverage/` | Test coverage reports |
| `.phpunit.cache/` | PHPUnit cache |
| `public/_debug*.php` | Dev-only diagnostic endpoints |

## Release Process

```bash
# 1. Ensure working tree is clean
git status

# 2. Tag the release
git tag v1.0.0

# 3. Build
./tools/build-dist.sh --no-dev

# 4. Package (optional)
cd wp-registration-plugin
zip -r ../imedia-registration-v1.0.0.zip wp-content/
```
