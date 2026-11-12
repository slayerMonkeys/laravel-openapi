<?php

use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use BIWEB\LaravelOpenapi\SchemaFactory;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Data\ItemlessData;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Data\NotASchema;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Data\ProfileData;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Data\RenamedData;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Data\TaskData;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Data\TeamData;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Data\UnionData;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Data\UserData;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Http\Resources\NamelessResource;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Http\Resources\UntypedResource;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Http\Resources\UserResource;

function profileSchema(string $version): array
{
    return (new SchemaFactory)->build(ProfileData::class)->toArray($version);
}

it('deduces the property schema from the PHP type', function (string $version, string $property, array $expected) {
    expect(profileSchema($version)['properties'][$property])->toBe($expected);
})->with([
    'int 3.0' => ['3.0.3', 'id', ['type' => 'integer']],
    'int 3.1' => ['3.1.0', 'id', ['type' => 'integer']],
    'float 3.0' => ['3.0.3', 'score', ['type' => 'number']],
    'float 3.1' => ['3.1.0', 'score', ['type' => 'number']],
    'string 3.0' => ['3.0.3', 'name', ['type' => 'string']],
    'string 3.1' => ['3.1.0', 'name', ['type' => 'string']],
    'bool 3.0' => ['3.0.3', 'active', ['type' => 'boolean']],
    'bool 3.1' => ['3.1.0', 'active', ['type' => 'boolean']],
    'nullable 3.0' => ['3.0.3', 'nickname', ['type' => 'string', 'nullable' => true]],
    'nullable 3.1' => ['3.1.0', 'nickname', ['type' => ['string', 'null']]],
    'non-promoted 3.1' => ['3.1.0', 'locale', ['type' => 'string']],
    'overrides 3.1' => ['3.1.0', 'email', ['type' => 'string', 'format' => 'email', 'description' => 'Login email', 'example' => 'jane@example.com']],
    'type override 3.0' => ['3.0.3', 'createdAt', ['type' => 'string', 'format' => 'date-time', 'nullable' => true]],
    'type override 3.1' => ['3.1.0', 'createdAt', ['type' => ['string', 'null'], 'format' => 'date-time']],
    'nullable override 3.0' => ['3.0.3', 'note', ['type' => 'string', 'nullable' => true]],
    'nullable override 3.1' => ['3.1.0', 'note', ['type' => ['string', 'null']]],
]);

it('lists required properties: not nullable and without default, unless explicit', function (string $version) {
    $schema = profileSchema($version);

    expect($schema['type'])->toBe('object')
        ->and($schema['required'])->toBe(['id', 'score', 'name', 'active', 'bio'])
        ->and(array_keys($schema['properties']))->not->toContain('secret', 'ignoredStatic');
})->with(['3.0.3', '3.1.0']);

it('produces a schema valid inside an OpenAPI document', function (string $version) {
    expect([
        'openapi' => $version,
        'info' => ['title' => 'API', 'version' => '1.0.0'],
        'paths' => new stdClass,
        'components' => ['schemas' => ['ProfileData' => profileSchema($version)]],
    ])->toBeValidOpenapi();
})->with(['3.0.3', '3.1.0']);

it('uses the name and description of #[Schema]', function () {
    $schema = (new SchemaFactory)->build(RenamedData::class);

    expect($schema->getObjectId())->toBe('Renamed')
        ->and($schema->toArray('3.1.0'))->toBe(['type' => 'object', 'description' => 'A renamed schema.']);
});

it('rejects a class without #[Schema]', function () {
    (new SchemaFactory)->build(NotASchema::class);
})->throws(InvalidOpenapiDefinition::class, 'Class ['.NotASchema::class.'] is not annotated with #[Schema].');

it('rejects a property whose type cannot be deduced', function () {
    (new SchemaFactory)->build(UnionData::class);
})->throws(InvalidOpenapiDefinition::class, 'Property ['.UnionData::class.'::$id]');

it('reads the properties declared on a JsonResource class instead of its PHP properties', function (string $version, array $nickname) {
    expect((new SchemaFactory)->build(UserResource::class)->toArray($version))->toBe([
        'type' => 'object',
        'properties' => [
            'id' => ['type' => 'integer'],
            'email' => ['type' => 'string', 'format' => 'email', 'description' => 'Login email'],
            'nickname' => $nickname,
            'avatar' => ['type' => 'string'],
        ],
        'required' => ['id', 'email'],
    ]);
})->with([
    '3.0' => ['3.0.3', ['type' => 'string', 'nullable' => true]],
    '3.1' => ['3.1.0', ['type' => ['string', 'null']]],
]);

it('rejects a class #[Property] without a name', function () {
    (new SchemaFactory)->build(NamelessResource::class);
})->throws(InvalidOpenapiDefinition::class, 'Class ['.NamelessResource::class.'] declares a #[Property] without a name.');

it('rejects a class #[Property] without a type', function () {
    (new SchemaFactory)->build(UntypedResource::class);
})->throws(InvalidOpenapiDefinition::class, 'Property ['.UntypedResource::class.'::$id]');

it('leaves a #[Schema] class as a class-string reference, resolved by Components', function () {
    $schema = built(UserData::class, '3.1.0');

    expect($schema['properties']['team'])->toBe(['$ref' => '#/'.TeamData::class]);
});

it('describes an array by its items', function (string $version) {
    expect(built(UserData::class, $version)['properties']['tags'])->toBe(['type' => 'array', 'items' => ['type' => 'string']]);
})->with(['3.0.3', '3.1.0']);

it('rejects an array without items', function () {
    (new SchemaFactory)->build(ItemlessData::class);
})->throws(InvalidOpenapiDefinition::class, 'Property ['.ItemlessData::class.'::$tags] is an array without items');

it('describes a backed enum by its backing type and values', function (string $class, string $property, string $version, array $expected) {
    expect(built($class, $version)['properties'][$property])->toBe($expected);
})->with([
    'string' => [UserData::class, 'status', '3.1.0', ['type' => 'string', 'enum' => ['active', 'banned']]],
    'int' => [TaskData::class, 'priority', '3.1.0', ['type' => 'integer', 'enum' => [1, 2]]],
    'nullable 3.0' => [UserData::class, 'previousStatus', '3.0.3', ['type' => 'string', 'nullable' => true, 'enum' => ['active', 'banned']]],
    'nullable 3.1' => [UserData::class, 'previousStatus', '3.1.0', ['type' => ['string', 'null'], 'enum' => ['active', 'banned', null]]],
]);

// nested classes stay class-string references until Components resolves them
function built(string $class, string $version): array
{
    return (new SchemaFactory)->build($class)->resolveRefs(fn (string $nested) => "#/{$nested}")->toArray($version);
}
