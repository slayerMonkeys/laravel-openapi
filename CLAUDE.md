# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project state

`biweb/laravel-openapi` is a Laravel package (not an application) generated from the Spatie `package-skeleton-laravel` template. It is still at skeleton stage: the main class, command, config, factory and migration are placeholders, and the README is the template's boilerplate. There is no git repository yet.

The skeleton's tooling files are **not present**: no `tests/` directory, `phpunit.xml(.dist)`, `phpstan.neon(.dist)`, `pint.json` or `workbench/`. `composer.json` already references `tests/` (namespace `BIWEB\LaravelOpenapi\Tests\`) and `workbench/app/` (namespace `Workbench\App\`), so create them at those paths. `configure.php` is the skeleton's one-shot setup script; the placeholders have already been replaced, so don't run it again.

## Commands

Requires PHP ^8.3. Supports Laravel 12/13 (`illuminate/contracts`), tested via Orchestra Testbench 10/11.

```bash
composer install
composer test                 # vendor/bin/pest
composer test-coverage        # vendor/bin/pest --coverage
composer analyse              # vendor/bin/phpstan analyse (Larastan), needs a phpstan.neon(.dist)
composer format               # vendor/bin/pint
vendor/bin/pest tests/SomeTest.php          # a single file
vendor/bin/pest --filter "test description" # a single test
```

`composer prepare` (run automatically after `dump-autoload`) runs `testbench package:discover`, which boots the package inside Testbench.

## Architecture

- Namespace `BIWEB\LaravelOpenapi\` → `src/`; factories under `BIWEB\LaravelOpenapi\Database\Factories\` → `database/factories/`.
- `src/LaravelOpenapiServiceProvider.php` extends Spatie's `PackageServiceProvider`. Everything the package registers (config, views, migrations, commands, routes…) is declared in `configurePackage()` through `spatie/laravel-package-tools`, not with hand-written `register()`/`boot()` logic. The provider and the `LaravelOpenapi` facade alias are auto-discovered via `extra.laravel` in `composer.json`.
- The package name is `laravel-openapi`. Package-tools derives a **short name** by stripping the `laravel-` prefix (`openapi`): that's why the config file is `config/openapi.php` (read with `config('openapi.*')`). Publish tags and the view namespace also use the short name: `openapi-config`, `openapi-migrations`, `openapi-views`, `view('openapi::…')`.
- Testbench doesn't load the provider automatically (there's no `testbench.yaml`), so `vendor/bin/testbench vendor:publish …` shows nothing until one is added or the provider is registered in the tests' `TestCase`.
- Migrations are `.php.stub` files in `database/migrations/`, which get published into the host app. The name passed to `hasMigration()` must match the stub's filename without extension (e.g. `create_openapi_table` ↔ `create_openapi_table.php.stub`).
- `src/Facades/LaravelOpenapi.php` resolves the `BIWEB\LaravelOpenapi\LaravelOpenapi` class from the container.
- `src/Commands/LaravelOpenapiCommand.php` is the placeholder Artisan command (`php artisan laravel-openapi`).
