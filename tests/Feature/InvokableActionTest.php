<?php

use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use BIWEB\LaravelOpenapi\Facades\LaravelOpenapi;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Http\Actions\AmbiguousAction;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Http\Actions\DeleteUserAction;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Http\Actions\ShowUserAction;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Http\UserController;
use Illuminate\Support\Facades\Route;

it('reads invokable actions annotated on the class or on __invoke like a classic controller', function (string $version) {
    config()->set('openapi.documents.default.openapi', $version);
    Route::put('api/users/{user}', [UserController::class, 'update']);
    Route::get('api/users/{user}', ShowUserAction::class);
    Route::delete('api/users/{user}', DeleteUserAction::class);

    $document = LaravelOpenapi::generate();
    $classic = $document['paths']['/api/users/{user}']['put'];

    expect($document['paths']['/api/users/{user}'])->toBe([
        'put' => $classic,
        'get' => ['summary' => 'Show a user'] + $classic,
        'delete' => ['summary' => 'Delete a user'] + $classic,
    ]);

    if (str_starts_with($version, '3.1')) {
        expect($document)->toBeValidOpenapi();
    }
})->with(['3.0.3', '3.1.0']);

it('ignores class-level attributes on a classic controller', function () {
    Route::get('api/undocumented', [UserController::class, 'undocumented']);

    expect(json_encode(LaravelOpenapi::generate()['paths']))->toBe('{}');
});

it('rejects an action annotated on both the class and __invoke', function () {
    Route::get('api/ambiguous', AmbiguousAction::class);

    LaravelOpenapi::generate();
})->throws(InvalidOpenapiDefinition::class, 'Action ['.AmbiguousAction::class.'] has operation attributes on both the class and __invoke.');
