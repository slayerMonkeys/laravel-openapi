<?php

use BIWEB\LaravelOpenapi\Components;
use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Schema;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Data\A;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Data\B;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Data\C;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Data\UserData;

it('resolves a circular reference into two components', function (string $version) {
    $components = new Components;

    expect($components->resolve(Schema::ref(UserData::class))->toArray($version))->toBe(['$ref' => '#/components/schemas/UserData']);

    $schemas = $components->toObject()->toArray($version)['schemas'];

    expect(array_keys($schemas))->toBe(['UserData', 'TeamData'])
        ->and($schemas['UserData']['properties']['team'])->toBe(['$ref' => '#/components/schemas/TeamData'])
        ->and($schemas['TeamData']['properties']['owner'])->toBe(['$ref' => '#/components/schemas/UserData'])
        ->and($schemas['TeamData']['properties']['members'])->toBe(['type' => 'array', 'items' => ['$ref' => '#/components/schemas/UserData']])
        ->and([
            'openapi' => $version,
            'info' => ['title' => 'API', 'version' => '1.0.0'],
            'paths' => new stdClass,
            'components' => $components->toObject()->toArray($version),
        ])->toBeValidOpenapi();
})->with(['3.0.3', '3.1.0']);

it('keeps the pointers already in the tree', function () {
    expect((new Components)->resolve(Schema::ref('#/components/schemas/Raw'))->toArray('3.1.0'))->toBe(['$ref' => '#/components/schemas/Raw']);
});

it('emits no component when nothing is referenced', function () {
    $components = new Components;
    $components->resolve(Schema::object()->properties(Schema::string('name')));

    expect($components->toObject())->toBeNull();
});

it('rejects two classes with the same component name', function () {
    $components = new Components;
    $components->resolve(Schema::ref(A\User::class));
    $components->resolve(Schema::ref(B\User::class));
})->throws(InvalidOpenapiDefinition::class, 'Classes ['.A\User::class.'] and ['.B\User::class.'] are both named [User]');

it('resolves a name collision with #[Schema(name:)]', function () {
    $components = new Components;
    $components->resolve(Schema::ref(A\User::class));
    $components->resolve(Schema::ref(C\User::class));

    expect(array_keys($components->toObject()->toArray('3.1.0')['schemas']))->toBe(['User', 'CUser']);
});
