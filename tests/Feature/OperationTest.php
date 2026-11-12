<?php

use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use BIWEB\LaravelOpenapi\Facades\LaravelOpenapi;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Http\UserController;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('api/users', [UserController::class, 'index']);
    Route::get('api/users/{user}', [UserController::class, 'show']);
    Route::put('api/users/{user}', [UserController::class, 'update']);
    Route::get('api/teams/{team?}', [UserController::class, 'team']);
    Route::get('api/undocumented', [UserController::class, 'undocumented']);
    Route::get('api/closure', fn () => 'ok');
    Route::get('web/users', [UserController::class, 'index']);
    Route::get('apiary', [UserController::class, 'index']);
});

it('documents only annotated routes within the prefixes', function (string $version) {
    config()->set('openapi.documents.default.openapi', $version);

    $document = LaravelOpenapi::generate();

    expect($document['paths'])->toEqual([
        '/api/users' => [
            'get' => [
                'tags' => ['users'],
                'summary' => 'List users',
                'description' => 'Paginated list.',
                'parameters' => [
                    ['name' => 'page', 'in' => 'query', 'description' => 'Page number', 'schema' => ['type' => 'integer']],
                    ['name' => 'X-Tenant', 'in' => 'header', 'required' => true, 'schema' => ['type' => 'string']],
                ],
            ],
        ],
        '/api/users/{user}' => [
            'get' => [
                'summary' => 'Show a user',
                'operationId' => 'showUser',
                'parameters' => [
                    ['name' => 'user', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
                ],
                'security' => [['bearer' => []]],
            ],
            'put' => [
                'summary' => 'Update a user',
                'parameters' => [
                    ['name' => 'user', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer', 'format' => 'int64']],
                ],
                'deprecated' => true,
                'security' => [],
            ],
        ],
        '/api/teams/{team}' => [
            'get' => [
                'summary' => 'Show a team',
                'parameters' => [
                    ['name' => 'team', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
                ],
            ],
        ],
    ]);

    // 3.0 requires a non-empty responses map on each operation, which comes with #[Response] (T6)
    if (str_starts_with($version, '3.1')) {
        expect($document)->toBeValidOpenapi();
    }
})->with(['3.0.3', '3.1.0']);

it('rejects an operationId on a route with several HTTP methods', function () {
    Route::match(['put', 'patch'], 'api/users/{user}/replace', [UserController::class, 'replace']);

    LaravelOpenapi::generate();
})->throws(InvalidOpenapiDefinition::class, 'Operation ['.UserController::class.'@replace] has an operationId');
