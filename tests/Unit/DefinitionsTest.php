<?php

use BIWEB\LaravelOpenapi\Components;
use BIWEB\LaravelOpenapi\Definitions;
use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Info;
use BIWEB\LaravelOpenapi\OpenApi\Objects\OpenApi;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Operation;
use BIWEB\LaravelOpenapi\OpenApi\Objects\PathItem;
use BIWEB\LaravelOpenapi\OpenApi\Objects\RequestBody;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Response;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Schema;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Data\OrgData;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Data\UserData;
use BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\Parameters\PaginationParameters;
use BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\RequestBodies\CreateUserBody;
use BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\Responses\NoContentResponse;
use BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\Responses\UserCreatedResponse;
use BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\Schemas\MoneySchema;
use BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\Schemas\TreeSchema;
use BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\Schemas\UserSchema;

function componentsArray(Components $components): array
{
    return $components->toObject()->toArray('3.1.0');
}

it('references a reusable schema and registers it under its objectId', function () {
    $components = new Components;

    expect($components->schema(UserSchema::class)->toArray('3.1.0'))->toBe(['$ref' => '#/components/schemas/User'])
        ->and(componentsArray($components)['schemas']['User'])->toBe([
            'type' => 'object',
            'properties' => ['id' => ['type' => 'integer'], 'team' => ['$ref' => '#/components/schemas/TeamData']],
            'required' => ['id'],
        ]);
});

it('inlines a schema that is not reusable, built by the container', function () {
    config(['app.currency' => 'EUR']);
    $components = new Components;

    expect($components->resolve(Schema::object()->properties(Schema::ref(MoneySchema::class, 'price')))->toArray('3.1.0'))->toBe([
        'type' => 'object',
        'properties' => ['price' => ['type' => 'object', 'description' => 'Amount in EUR', 'properties' => ['amount' => ['type' => 'integer']]]],
    ])->and($components->toObject())->toBeNull();
});

it('mixes #[Schema] classes and schema definitions both ways', function () {
    $components = new Components;
    $components->schema(OrgData::class);
    $schemas = componentsArray($components)['schemas'];

    expect(array_keys($schemas))->toBe(['OrgData', 'User', 'TeamData', 'UserData'])
        ->and($schemas['OrgData']['properties']['members'])->toBe(['type' => 'array', 'items' => ['$ref' => '#/components/schemas/User']])
        ->and($schemas['User']['properties']['team'])->toBe(['$ref' => '#/components/schemas/TeamData']);
});

it('emits a reusable response under the status code of the built object', function () {
    $components = new Components;
    $operation = $components->resolve(Operation::post()->responses(Response::ref(UserCreatedResponse::class), Response::ref(NoContentResponse::class)));

    expect($operation->toArray('3.1.0')['responses'])->toBe([
        201 => ['$ref' => '#/components/responses/UserCreated'],
        204 => ['description' => 'No content'],
    ])->and(array_keys(componentsArray($components)['responses']))->toBe(['UserCreated']);
});

it('registers one component per parameter of a reusable Parameters', function () {
    $components = new Components;

    expect(array_map(fn ($parameter) => $parameter->toArray('3.1.0'), $components->parameters(PaginationParameters::class)))->toBe([
        ['$ref' => '#/components/parameters/page'],
        ['$ref' => '#/components/parameters/PerPage'],
    ])->and(componentsArray($components)['parameters'])->toBe([
        'page' => ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer']],
        'PerPage' => ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer']],
    ]);
});

it('inlines a request body that is not reusable', function () {
    expect((new Components)->resolve(Operation::post()->requestBody(RequestBody::ref(CreateUserBody::class)))->toArray('3.1.0')['requestBody'])->toBe([
        'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/User']]],
        'required' => true,
    ]);
});

it('builds a valid document from definitions', function (string $version) {
    $components = new Components;
    $operation = Operation::post()
        ->parameters(...$components->parameters(PaginationParameters::class))
        ->requestBody(RequestBody::ref(CreateUserBody::class))
        ->responses(Response::ref(UserCreatedResponse::class));

    $document = $components->resolve(OpenApi::create()
        ->openapi($version)
        ->info(Info::create()->title('API')->version('1.0.0'))
        ->paths(PathItem::create()->route('/users')->operations($operation)));

    expect($document->components($components->toObject())->toArray($version))->toBeValidOpenapi();
})->with(['3.0.3', '3.1.0']);

it('rejects a definition that inlines itself', function () {
    (new Components)->schema(TreeSchema::class);
})->throws(InvalidOpenapiDefinition::class, 'Definition ['.TreeSchema::class.'] references itself, which needs it to implement Reusable.');

it('rejects a class that is not a definition of the expected kind', function () {
    (new Components)->response(UserData::class);
})->throws(InvalidOpenapiDefinition::class, 'Class ['.UserData::class.'] does not extend ['.Definitions\Response::class.'].');
