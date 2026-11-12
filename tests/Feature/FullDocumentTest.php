<?php

use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use BIWEB\LaravelOpenapi\Facades\LaravelOpenapi;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Http\AccountController;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Http\Actions\AddMemberAction;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Http\Actions\CreateUserAction;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Http\ResponseController;
use BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\RequestBodies\StoreUserBody;
use BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\Responses\UserCreatedResponse;
use Illuminate\Support\Facades\Route;

function fullDocument(string $version): array
{
    config()->set('openapi.documents.default.openapi', $version);
    Route::get('api/users', [AccountController::class, 'index']);
    Route::post('api/users', [AccountController::class, 'store']);
    Route::get('api/users/export', [AccountController::class, 'export']);
    Route::get('api/users/{user}', [AccountController::class, 'show']);
    Route::delete('api/users/{user}', [AccountController::class, 'destroy']);
    Route::post('api/admin/users', CreateUserAction::class);
    Route::post('api/teams/{team}/members', AddMemberAction::class);

    return LaravelOpenapi::generate();
}

it('generates the full document, valid and equal to the expected file', function (string $version, string $file) {
    $document = fullDocument($version);

    expect($document)->toBeValidOpenapi()
        ->and(json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n")->toBe(file_get_contents(__DIR__."/../Fixtures/expected/{$file}"));
})->with([
    '3.0' => ['3.0.3', 'document-3.0.json'],
    '3.1' => ['3.1.0', 'document-3.1.json'],
]);

it('lists a schema as an array of references', function () {
    expect(fullDocument('3.1.0')['paths']['/api/users/export']['get']['responses'][200]['content']['application/json']['schema'])
        ->toBe(['type' => 'array', 'items' => ['$ref' => '#/components/schemas/UserData']]);
});

it('emits a response without schema without content', function () {
    expect(fullDocument('3.1.0')['paths']['/api/users/{user}']['delete']['responses'])->toBe([204 => ['description' => 'Deleted']]);
});

it('references a reusable response under its status code', function () {
    $document = fullDocument('3.1.0');

    expect($document['paths']['/api/teams/{team}/members']['post']['responses'])->toBe([201 => ['$ref' => '#/components/responses/UserCreated']])
        ->and($document['components']['responses'])->toHaveKey('UserCreated');
});

it('wraps a JsonResource in its $wrap key', function () {
    $document = fullDocument('3.1.0');
    $schema = fn (string $path) => $document['paths'][$path]['get']['responses'][200]['content']['application/json']['schema'];

    expect($schema('/api/users/{user}'))->toBe(['type' => 'object', 'properties' => ['data' => ['$ref' => '#/components/schemas/UserResource']], 'required' => ['data']])
        ->and($schema('/api/users'))->toBe(['type' => 'object', 'properties' => ['data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/UserResource']]], 'required' => ['data']]);
});

it('does not wrap a JsonResource whose $wrap is null', function () {
    Route::get('api/plain', [ResponseController::class, 'plain']);

    expect(LaravelOpenapi::generate()['paths']['/api/plain']['get']['responses'][200]['content']['application/json']['schema'])
        ->toBe(['$ref' => '#/components/schemas/PlainResource']);
});

it('gives the same operation from attributes and from definition classes', function () {
    $document = fullDocument('3.1.0');
    $withoutId = fn (array $operation) => array_diff_key($operation, ['operationId' => true]);

    expect($withoutId($document['paths']['/api/admin/users']['post']))->toBe($withoutId($document['paths']['/api/users']['post']));
});

it('merges #[Parameters] with #[Parameter]', function () {
    expect(fullDocument('3.1.0')['paths']['/api/users']['get']['parameters'])->toBe([
        ['$ref' => '#/components/parameters/page'],
        ['$ref' => '#/components/parameters/PerPage'],
        ['name' => 'search', 'in' => 'query', 'description' => 'Filter by email', 'schema' => ['type' => 'string']],
    ]);
});

it('rejects a definition class given other arguments', function (string $method, string $message) {
    Route::get('api/invalid', [ResponseController::class, $method]);

    expect(fn () => LaravelOpenapi::generate())->toThrow(InvalidOpenapiDefinition::class, $message);
})->with([
    '#[Response]' => ['definitionWithArguments', 'Response ['.UserCreatedResponse::class.'] of ['.ResponseController::class.'@definitionWithArguments] is a definition, which forbids the other arguments of #[Response].'],
    '#[RequestBody]' => ['bodyWithArguments', 'Request body ['.StoreUserBody::class.'] of ['.ResponseController::class.'@bodyWithArguments] is a definition, which forbids the other arguments of #[RequestBody].'],
]);
