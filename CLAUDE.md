# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project state

`biweb/laravel-openapi` is a Laravel package (not an application), started from the Spatie `package-skeleton-laravel`
template. It generates OpenAPI 3.0/3.1 documents from PHP attributes and definition classes. `SPEC.md` is the
contract (in French), and `tasks/plan.md` / `tasks/todo.md` track the v1 tasks and decisions.

There is no `workbench/`: test fixtures under `tests/Fixtures/` are enough. Git flow: feature branches off `develop`,
PRs into `develop`.

## Commands

Requires PHP ^8.3. Supports Laravel 12/13 (`illuminate/contracts`), tested via Orchestra Testbench 10/11.

```bash
composer install
composer test                 # vendor/bin/pest
composer test-coverage        # vendor/bin/pest --coverage
composer analyse              # vendor/bin/phpstan analyse (Larastan, level 5, src/ and config/)
composer format               # vendor/bin/pint
vendor/bin/pest tests/Feature/FullDocumentTest.php  # a single file
vendor/bin/pest --filter "test description"         # a single test
```

`composer prepare` (run automatically after `dump-autoload`) runs `testbench package:discover`, which boots the package
inside Testbench. CI also runs the suite with `--prefer-lowest`.

## Architecture

- Namespace `BIWEB\LaravelOpenapi\` → `src/`. The provider (`LaravelOpenapiServiceProvider`, Spatie
  `PackageServiceProvider`) declares the config, the routes file and the commands in `configurePackage()`, and
  publishes `stubs/` in `packageBooted()` (tag `openapi-stubs`). Package-tools strips the `laravel-` prefix, so the
  config is `config/openapi.php` (`config('openapi.*')`) and the tags are `openapi-*`.
- **Generation** (`LaravelOpenapi::generate(?string $document)`, also through the facade): validates the document's
  `openapi` version (3.0.x/3.1.x), reads `Route::getRoutes()` filtered by the document's URI prefixes, keeps the routes
  whose action carries `#[Operation]` (and whose `documents` list allows the document), and builds an `Objects\OpenApi`
  tree that is serialized by `->toArray($version)`. No OpenAPI array is built by hand outside `toArray()`.
  - Invokable actions: Laravel resolves them to `Class@__invoke`; attributes are read on `__invoke`, or on the class,
    never both.
  - `PathExpander` turns `{x?}` segments into *n* + 1 paths; an explicit `operationId` gets a `By{Param}` suffix.
- **Object model** (`src/OpenApi/Objects`, `Enums`, `Contracts`): immutable fluent objects, each setter returns a
  clone (`BaseObject::with()`). Lists are keyed at serialization by `objectId` (or status code, media type, route…).
  `Schema` holds the only 3.0/3.1 differences (`nullable`, exclusive bounds); `SINCE_3_1` fields are rejected in a 3.0
  document; empty maps are emitted as `stdClass` (`{}`). `BaseObject::ref()` takes a `#/…` pointer or a class-string,
  which must be resolved before serialization.
- **`Components`** (`src/Components.php`): one registry per generation, never global state. `resolve($tree)`
  replaces the class-string references of the tree's descendants (`resolveRefs()`) by a `#/components/…` pointer or by
  the inlined object. `schema()`, `response()`, `requestBody()` and `parameters()` resolve one class. A `#[Schema]`
  class or a `Reusable` definition is registered once (before its own references are resolved, so cycles stop) and
  emitted only if referenced; any other definition is inlined (an inline definition referencing itself throws). Name
  collisions are checked per components field.
- **`SchemaFactory`**: builds the `Objects\Schema` of a `#[Schema]` class from its PHP properties, or from the
  `#[Property]` declared on the class (API Resources, models). Nested classes stay class-string references for
  `Components`.
- **Definitions** (`src/Definitions`): abstract `Schema`, `Parameters`, `RequestBody`, `Response` with `build()`,
  resolved by the container, plus the `Reusable` marker interface.
- **Attributes** (`src/Attributes`): `Operation`, `Parameter`, `Parameters`, `RequestBody`, `Response`, `Schema`,
  `Property`, all `final readonly` with promoted constructors.
- **Commands**: `openapi:generate {document?} {--all} {--output=} {--format=}` writes JSON, or YAML (`symfony/yaml`) for
  a `.yaml`/`.yml` file or `--format=yaml`. `make:openapi-{schema,parameters,request-body,response}` extend
  `MakeDefinitionCommand` (`GeneratorCommand`): the stubs describe a reusable class, and the marker is removed
  without `--reusable`. A stub in the application's `stubs/` wins.
- **Route**: `routes/openapi.php` registers `GET {route.uri}` for each document with `route.enabled`, the document
  name as a route default (compatible with `route:cache`). `Http\OpenapiController` serves the `output` file as is,
  404 when it is missing.
- **Errors**: a single exception, `InvalidOpenapiDefinition`, whose message names the class, method or path at fault.
  The command catches it and returns `FAILURE`.

## Tests

- Pest + Testbench; `tests/TestCase.php` registers the provider. Feature tests register their fixture routes with
  `Route::…` inside the test.
- `expect($document)->toBeValidOpenapi()` (`tests/Pest.php`) validates against the official meta-schemas in
  `tests/Fixtures/schemas/`: `justinrainbow/json-schema` for 3.0 (draft-04), `opis/json-schema` for 3.1.
- `tests/Fixtures/expected/document-3.{0,1}.json` are the expected full documents (`FullDocumentTest`); regenerate
  them only on purpose, and review the diff.
