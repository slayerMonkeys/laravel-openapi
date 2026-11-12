<?php

use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use BIWEB\LaravelOpenapi\Facades\LaravelOpenapi;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Http\AccountController;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Http\InternalController;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config()->set('openapi.documents', [
        'public' => [
            'openapi' => '3.1.0',
            'info' => ['title' => 'Public API', 'version' => '1.0.0'],
            'routes' => ['prefixes' => ['api']],
            'security_schemes' => [
                'bearer' => ['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 'JWT'],
                'key' => ['type' => 'apiKey', 'name' => 'X-Key', 'in' => 'header'],
                'oauth' => ['type' => 'oauth2', 'flows' => ['clientCredentials' => ['tokenUrl' => 'https://example.com/token', 'scopes' => ['read' => 'Read']]]],
            ],
            'security' => [['bearer' => []], ['oauth' => ['read']]],
        ],
        'internal' => [
            'openapi' => '3.0.3',
            'info' => ['title' => 'Internal API', 'version' => '1.0.0'],
            'routes' => ['prefixes' => ['internal']],
        ],
    ]);

    Route::get('api/users/export', [AccountController::class, 'export']);
    Route::get('api/audits', [InternalController::class, 'audits']);
    Route::get('internal/tasks', [InternalController::class, 'tasks']);
    Route::get('internal/audits', [InternalController::class, 'audits']);
});

it('keeps in each document only its routes and their components', function () {
    $public = LaravelOpenapi::generate('public');
    $internal = LaravelOpenapi::generate('internal');

    expect(array_keys($public['paths']))->toBe(['/api/users/export'])
        ->and(array_keys($public['components']['schemas']))->toBe(['UserData', 'TeamData'])
        ->and(array_keys($internal['paths']))->toBe(['/internal/tasks', '/internal/audits'])
        ->and($internal['components'])->toBe(['schemas' => ['TaskData' => [
            'type' => 'object',
            'properties' => ['priority' => ['type' => 'integer', 'enum' => [1, 2]]],
            'required' => ['priority'],
        ]]])
        ->and($public)->toBeValidOpenapi()
        ->and($internal)->toBeValidOpenapi();
});

it('emits the security schemes and the global security of the document', function () {
    $document = LaravelOpenapi::generate('public');

    expect($document['security'])->toBe([['bearer' => []], ['oauth' => ['read']]])
        ->and($document['components']['securitySchemes'])->toBe([
            'bearer' => ['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 'JWT'],
            'key' => ['type' => 'apiKey', 'name' => 'X-Key', 'in' => 'header'],
            'oauth' => ['type' => 'oauth2', 'flows' => ['clientCredentials' => ['tokenUrl' => 'https://example.com/token', 'scopes' => ['read' => 'Read']]]],
        ])
        ->and(LaravelOpenapi::generate('internal'))->not->toHaveKey('security');
});

it('emits the security schemes of a document without any component', function () {
    config()->set('openapi.documents.public.routes.prefixes', []);

    expect(LaravelOpenapi::generate('public')['components'])->toHaveKeys(['securitySchemes'])->not->toHaveKey('schemas');
});

it('rejects an unknown security scheme type', function () {
    config()->set('openapi.documents.public.security_schemes', ['basic' => ['type' => 'basic']]);

    LaravelOpenapi::generate('public');
})->throws(InvalidOpenapiDefinition::class, 'Security scheme [basic] has an unknown type [basic].');
