# Laravel OpenAPI

[![Latest Version on Packagist](https://img.shields.io/packagist/v/biweb/laravel-openapi.svg?style=flat-square)](https://packagist.org/packages/biweb/laravel-openapi)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/slayerMonkeys/laravel-openapi/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/slayerMonkeys/laravel-openapi/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/slayerMonkeys/laravel-openapi/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/slayerMonkeys/laravel-openapi/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/biweb/laravel-openapi.svg?style=flat-square)](https://packagist.org/packages/biweb/laravel-openapi)

Generate an OpenAPI 3.0 or 3.1 document from PHP attributes on your routes, or from definition classes built with a
fluent object model. Nothing is inferred from FormRequests, validation rules or return types: what you declare is what
you get, and the generated document is valid against the official meta-schema of its version.

It works with classic controllers and with invokable actions (ADR), and handles several documents per application
(for example `public` and `internal`).

## Installation

Requires PHP 8.3+ and Laravel 12 or 13.

```bash
composer require biweb/laravel-openapi
php artisan vendor:publish --tag="openapi-config"
```

## Configuration

`config/openapi.php` declares one or more documents. Only the routes whose URI starts with one of the document's
`prefixes` **and** whose action carries `#[Operation]` are documented.

```php
return [
    'default' => 'default',

    'documents' => [
        'default' => [
            'openapi' => '3.1.0', // any 3.0.x or 3.1.x
            'info' => [
                'title' => 'API',
                'version' => '1.0.0',
                'description' => null,
            ],
            'servers' => [
                // ['url' => 'https://api.example.com'],
            ],
            'routes' => [
                'prefixes' => ['api'],
            ],
            // emitted in components.securitySchemes, keyed by name
            'security_schemes' => [
                // 'bearer' => ['type' => 'http', 'scheme' => 'bearer'],
            ],
            'security' => [], // the global requirement, e.g. [['bearer' => []]]
            'output' => base_path('openapi.json'), // a .yaml/.yml extension gives YAML
            'route' => [
                'enabled' => false,
                'uri' => 'openapi/default.json',
                'middleware' => [],
            ],
        ],
    ],
];
```

Add a document by adding a key under `documents`, with its own version, prefixes, security and `output`.
`#[Operation(documents: ['internal'])]` restricts an operation to some documents (an empty list means all of them).
A component only appears in the documents that reference it.

## Documenting a route

```php
use BIWEB\LaravelOpenapi\Attributes\Operation;
use BIWEB\LaravelOpenapi\Attributes\Parameter;
use BIWEB\LaravelOpenapi\Attributes\Parameters;
use BIWEB\LaravelOpenapi\Attributes\RequestBody;
use BIWEB\LaravelOpenapi\Attributes\Response;

class AccountController
{
    #[Operation(summary: 'List users', operationId: 'listUsers', tags: ['users'])]
    #[Parameters(PaginationParameters::class)]
    #[Parameter(name: 'search', description: 'Filter by email')]
    #[Response(200, 'Users', schema: UserResource::class, isList: true)]
    public function index() { /* … */ }

    #[Operation(summary: 'Create a user', operationId: 'storeUser', tags: ['users'])]
    #[RequestBody(UserData::class)]
    #[Response(201, 'User created', schema: UserData::class)]
    #[Response(422, 'Validation error')]
    public function store() { /* … */ }

    #[Operation(summary: 'Show a user', operationId: 'showUser', tags: ['users'])]
    #[Parameter(name: 'user', type: 'integer')]
    #[Response(200, 'User', schema: UserResource::class)]
    #[Response(404, 'Not found')]
    public function show(int $user) { /* … */ }
}
```

| Attribute                                                                          | Notes                                                                                                                                                                      |
|------------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `Operation(summary, description, operationId, tags, deprecated, security, documents)` | Opt-in: a route without it is not documented. `security: null` inherits the document's, `security: []` disables it. `operationId` is never generated.                   |
| `Parameter(name, in, type, format, required, description)`                         | Repeatable. `in` defaults to `path` when the URI has a `{name}` segment, `query` otherwise. Path segments are added automatically as `string`, unless redefined.           |
| `Parameters(...classes)`                                                           | Repeatable. `Parameters` definition classes, merged with `#[Parameter]` (unique on `in` + `name`, `#[Parameter]` wins).                                                    |
| `RequestBody(body, contentType, required, description)`                            | `body` is a `RequestBody` definition class, or a schema: a `#[Schema]` class or a `Schema` definition class.                                                              |
| `Response(status, description, schema, contentType, isList)`                       | Repeatable. `status` is an HTTP code, or a `Response` definition class (the other arguments are then forbidden). `isList: true` gives `{type: array, items: $ref}`.       |

**Invokable actions (ADR)**: for `Route::post('admin/users', CreateUserAction::class)`, the attributes can be put on
the class or on `__invoke`, but not on both.

```php
#[Operation(summary: 'Create a user', operationId: 'createUser', tags: ['users'])]
#[RequestBody(StoreUserBody::class)]
#[Response(UserStoredResponse::class)]
#[Response(422, 'Validation error')]
final class CreateUserAction
{
    public function __invoke() { /* … */ }
}
```

**Optional segments**: OpenAPI has no optional path segment, so `api/posts/{post}/comments/{comment?}` gives two paths,
`/api/posts/{post}/comments` and `/api/posts/{post}/comments/{comment}`. Each one only has its own path parameters, and
an explicit `operationId` gets a suffix per segment present (`listComments`, `listCommentsByComment`).

## Schemas

A class annotated with `#[Schema]` becomes a component of `components.schemas`, named after the class
(`#[Schema(name: …)]` renames it) and referenced by `$ref`.

```php
use BIWEB\LaravelOpenapi\Attributes\Property;
use BIWEB\LaravelOpenapi\Attributes\Schema;

#[Schema]
final class UserData
{
    public function __construct(
        public TeamData $team,                                  // $ref to the TeamData component
        public Status $status,                                  // a BackedEnum: its backing type and values
        public ?Status $previousStatus,                         // nullable, so not required
        #[Property(items: 'string')] public array $tags,        // an array needs its items
    ) {}
}
```

| PHP type                            | Schema                                                         |
|-------------------------------------|----------------------------------------------------------------|
| `int` / `float` / `string` / `bool` | `integer` / `number` / `string` / `boolean`                    |
| `array`                             | `array`, with `items` required through `#[Property(items: …)]` |
| a `#[Schema]` class                 | `$ref`                                                         |
| `BackedEnum`                        | the backing type, and its values in `enum`                     |
| `?T`                                | nullable                                                       |

A property is required when it is not nullable and has no default value. `#[Property(type:, format:, description:,
nullable:, required:, example:, items:)]` overrides what is deduced.

**API Resources and models**: their fields are not PHP properties, so they are declared on the class. Once a class has
a `#[Property]`, its PHP properties are not read, and `toArray()` never is.

```php
#[Schema]
#[Property('id', type: 'integer')]
#[Property('email', type: 'string', format: 'email', description: 'Login email')]
#[Property('nickname', type: 'string', nullable: true)]
#[Property('avatar', type: 'string', required: false)]
final class UserResource extends JsonResource { /* … */ }
```

A response whose schema is a `JsonResource` follows its `$wrap` (`data` by default):
`{type: object, properties: {data: $ref}, required: [data]}`, or `data: {type: array, items: $ref}` with `isList`.
When `$wrap` is `null`, the schema is not wrapped.

## Object model and definition classes

The document is a tree of immutable, fluent objects (`BIWEB\LaravelOpenapi\OpenApi\Objects\*`): each setter returns a
clone. They describe an intent, serialized for the document's version (`->nullable()` gives `nullable: true` in 3.0 and
`type: [T, "null"]` in 3.1).

Definition classes extend `BIWEB\LaravelOpenapi\Definitions\{Schema, Parameters, RequestBody, Response}` and build an
object in `build()`. They are resolved by the container, so their constructor can take dependencies.

```php
use BIWEB\LaravelOpenapi\Definitions\Response;
use BIWEB\LaravelOpenapi\Definitions\Reusable;
use BIWEB\LaravelOpenapi\OpenApi\Objects;

final class UserCreatedResponse extends Response implements Reusable
{
    public function build(): Objects\Response
    {
        return Objects\Response::created('UserCreated')
            ->description('User created')
            ->content(Objects\MediaType::json()->schema(Objects\Schema::ref(UserSchema::class)));
    }
}
```

- With `Reusable`, the class goes in `components` and is referenced by `$ref`: `#[Response(UserCreatedResponse::class)]`
  gives `responses.201 = {$ref: '#/components/responses/UserCreated'}`, the status code being read on the built
  object. Without `Reusable`, the object is inlined.
- The component is named after the `objectId` given to the builder (`Response::created('UserCreated')`), otherwise
  after the short class name. A reusable `Parameters` class gives one component per parameter, named after its
  `objectId`, otherwise its `name`.
- `Schema::ref(UserData::class)` in a `build()` can target a `#[Schema]` class, and `#[Property(items: UserSchema::class)]`
  can target a `Schema` definition: both produce the same object model.

```php
final class PaginationParameters extends Parameters implements Reusable
{
    public function build(): array
    {
        return [
            Objects\Parameter::query()->name('page')->schema(Objects\Schema::integer()),
            Objects\Parameter::query('PerPage')->name('per_page')->schema(Objects\Schema::integer()),
        ];
    }
}
```

Generate a definition class in `app/OpenApi/{Schemas,Parameters,RequestBodies,Responses}`:

```bash
php artisan make:openapi-schema User                 # app/OpenApi/Schemas/UserSchema.php
php artisan make:openapi-parameters Pagination
php artisan make:openapi-request-body CreateUser
php artisan make:openapi-response UserCreated --reusable
```

The stubs can be published (`php artisan vendor:publish --tag="openapi-stubs"`) and edited in `stubs/`.

## Generating the document

```bash
php artisan openapi:generate                 # the default document, to its output
php artisan openapi:generate internal        # another document
php artisan openapi:generate --all           # every document, each to its output
php artisan openapi:generate --output=public/openapi.yaml
php artisan openapi:generate --format=yaml   # otherwise deduced from the file extension
```

The document is also available as an array: `LaravelOpenapi::generate(?string $document = null)`.

An invalid declaration (unknown document, class without `#[Schema]`, array without `items`, two classes with the same
component name, …) throws `InvalidOpenapiDefinition` with a message naming the class or method at fault, and the
command exits with a failure code.

**Run `openapi:generate` on each deployment** (for example in your deploy script, after `composer install`): the
document is a file, so it does not follow your code by itself.

## Serving the document

With `route.enabled`, `GET {route.uri}` serves the document's `output` file as is, through the configured middleware,
as `application/json`, or `application/yaml` for a `.yaml`/`.yml` file. It answers 404 until the file is generated, and
it works with `route:cache`. A file written elsewhere with `--output` is not served.

## Testing

```bash
composer test
composer analyse
composer format
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [SlayerMonkey](https://github.com/slayerMonkeys)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
