<?php

use BIWEB\LaravelOpenapi\OpenApi\Enums\ParameterIn;
use BIWEB\LaravelOpenapi\OpenApi\Enums\SchemaFormat;
use BIWEB\LaravelOpenapi\OpenApi\Enums\Style;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Components;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Info;
use BIWEB\LaravelOpenapi\OpenApi\Objects\MediaType;
use BIWEB\LaravelOpenapi\OpenApi\Objects\OpenApi;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Operation;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Parameter;
use BIWEB\LaravelOpenapi\OpenApi\Objects\PathItem;
use BIWEB\LaravelOpenapi\OpenApi\Objects\RequestBody;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Response;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Schema;
use BIWEB\LaravelOpenapi\OpenApi\Objects\SecurityRequirement;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Server;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Tag;

// [object without the last setter, the last setter, expected output] — same output in 3.0 and 3.1
dataset('objects', fn () => [
    'Info' => [
        Info::create()->title('API')->version('1.0.0'),
        fn (Info $info) => $info->description('Users API')->termsOfService('https://example.com/terms'),
        ['title' => 'API', 'description' => 'Users API', 'termsOfService' => 'https://example.com/terms', 'version' => '1.0.0'],
    ],
    'Server' => [
        Server::create()->url('https://api.example.com'),
        fn (Server $server) => $server->description('Production'),
        ['url' => 'https://api.example.com', 'description' => 'Production'],
    ],
    'Tag' => [
        Tag::create()->name('users'),
        fn (Tag $tag) => $tag->description('User management'),
        ['name' => 'users', 'description' => 'User management'],
    ],
    'PathItem' => [
        PathItem::create()->route('/users')->summary('Users'),
        fn (PathItem $path) => $path->operations(Operation::get(), Operation::post()->summary('Create'))
            ->parameters(Parameter::header()->name('X-Tenant')),
        ['summary' => 'Users', 'get' => new stdClass, 'post' => ['summary' => 'Create'], 'parameters' => [['name' => 'X-Tenant', 'in' => 'header']]],
    ],
    'Operation' => [
        Operation::get()->summary('List users'),
        fn (Operation $operation) => $operation->tags('users', Tag::create()->name('admin'))->operationId('listUsers')
            ->parameters(Parameter::query()->name('page'))
            ->responses(Response::ok(), Response::create()->description('Error'))
            ->deprecated()
            ->security(SecurityRequirement::create()->securityScheme('bearer')),
        [
            'tags' => ['users', 'admin'],
            'summary' => 'List users',
            'operationId' => 'listUsers',
            'parameters' => [['name' => 'page', 'in' => 'query']],
            'responses' => [200 => ['description' => 'OK'], 'default' => ['description' => 'Error']],
            'deprecated' => true,
            'security' => [['bearer' => []]],
        ],
    ],
    'Parameter' => [
        Parameter::path()->name('id'),
        fn (Parameter $parameter) => $parameter->description('User id')->required()->style(Style::SIMPLE)->explode(false)
            ->schema(Schema::integer()->format(SchemaFormat::INT64))->example(42),
        ['name' => 'id', 'in' => 'path', 'description' => 'User id', 'required' => true, 'style' => 'simple', 'explode' => false,
            'schema' => ['type' => 'integer', 'format' => 'int64'], 'example' => 42],
    ],
    'RequestBody' => [
        RequestBody::create()->content(MediaType::json()->schema(Schema::object())),
        fn (RequestBody $body) => $body->description('New user')->required(),
        ['description' => 'New user', 'content' => ['application/json' => ['schema' => ['type' => 'object']]], 'required' => true],
    ],
    'MediaType' => [
        MediaType::formData(),
        fn (MediaType $mediaType) => $mediaType->schema(Schema::string()->format(SchemaFormat::BINARY))->example('file.png'),
        ['schema' => ['type' => 'string', 'format' => 'binary'], 'example' => 'file.png'],
    ],
    'Response' => [
        Response::created(),
        fn (Response $response) => $response->content(MediaType::json()->schema(Schema::ref('#/components/schemas/User'))),
        ['description' => 'Created', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/User']]]],
    ],
    'Components' => [
        Components::create()->schemas(Schema::object('User')),
        fn (Components $components) => $components->responses(Response::notFound('NotFound'))
            ->parameters(Parameter::query('Page')->name('page'))
            ->requestBodies(RequestBody::create('NewUser')->content(MediaType::json())),
        [
            'schemas' => ['User' => ['type' => 'object']],
            'responses' => ['NotFound' => ['description' => 'Not Found']],
            'parameters' => ['Page' => ['name' => 'page', 'in' => 'query']],
            'requestBodies' => ['NewUser' => ['content' => ['application/json' => new stdClass]]],
        ],
    ],
    'SecurityRequirement' => [
        SecurityRequirement::create()->securityScheme('oauth'),
        fn (SecurityRequirement $requirement) => $requirement->scopes('read', 'write'),
        ['oauth' => ['read', 'write']],
    ],
    'OpenApi' => [
        OpenApi::create()->openapi('3.1.0')->info(Info::create()->title('API')->version('1.0.0')),
        fn (OpenApi $openapi) => $openapi->servers(Server::create()->url('https://api.example.com'))
            ->paths(PathItem::create()->route('/users'))
            ->security(SecurityRequirement::create()->securityScheme('bearer'))
            ->tags(Tag::create()->name('users')),
        [
            'openapi' => '3.1.0',
            'info' => ['title' => 'API', 'version' => '1.0.0'],
            'servers' => [['url' => 'https://api.example.com']],
            'paths' => ['/users' => new stdClass],
            'security' => [['bearer' => []]],
            'tags' => [['name' => 'users']],
        ],
    ],
]);

it('builds each object without modifying the original', function (object $original, Closure $set, array $expected) {
    $before = serialize($original);

    expect(json_encode($set($original)->toArray('3.0.3')))->toBe(json_encode($expected))
        ->and(json_encode($set($original)->toArray('3.1.0')))->toBe(json_encode($expected))
        ->and(serialize($original))->toBe($before);
})->with('objects');

it('names extensions x-*', function () {
    expect(Tag::create()->name('users')->x('internal', true)->x('x-order', 2)->toArray('3.1.0'))
        ->toBe(['name' => 'users', 'x-internal' => true, 'x-order' => 2]);
});

it('emits an empty object as {} and keeps the explicit empty security of an operation', function () {
    $operation = Operation::get()->noSecurity()->responses(Response::ok()->content(MediaType::json()->schema(Schema::create())));

    expect(json_encode($operation->toArray('3.1.0')))->toBe('{"responses":{"200":{"description":"OK","content":{"application\/json":{"schema":{}}}}},"security":[]}')
        ->and(json_encode(Operation::get()->security(SecurityRequirement::create())->toArray('3.1.0')))->toBe('{"security":[{}]}')
        ->and(json_encode(OpenApi::create()->openapi('3.1.0')->info(Info::create()->title('API')->version('1'))->toArray('3.1.0')['paths']))->toBe('{}');
});

it('serializes enums by their value', function () {
    expect(Parameter::create()->name('session')->in(ParameterIn::COOKIE)->toArray('3.1.0'))->toBe(['name' => 'session', 'in' => 'cookie']);
});
