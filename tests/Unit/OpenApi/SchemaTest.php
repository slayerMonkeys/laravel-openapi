<?php

use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use BIWEB\LaravelOpenapi\OpenApi\Enums\SchemaFormat;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Schema;

it('serializes the 3.0 and 3.1 differences', function (Schema $schema, array $v30, array $v31) {
    expect($schema->toArray('3.0.3'))->toBe($v30)
        ->and($schema->toArray('3.1.0'))->toBe($v31);
})->with([
    'nullable' => [Schema::string()->nullable(), ['type' => 'string', 'nullable' => true], ['type' => ['string', 'null']]],
    'not nullable' => [Schema::string()->nullable(false), ['type' => 'string', 'nullable' => false], ['type' => 'string']],
    'nullable enum' => [
        Schema::string()->enum('draft', 'published')->nullable(),
        ['type' => 'string', 'nullable' => true, 'enum' => ['draft', 'published']],
        ['type' => ['string', 'null'], 'enum' => ['draft', 'published', null]],
    ],
    'exclusive bounds' => [
        Schema::number()->exclusiveMinimum(0)->exclusiveMaximum(1.5),
        ['type' => 'number', 'maximum' => 1.5, 'exclusiveMaximum' => true, 'minimum' => 0, 'exclusiveMinimum' => true],
        ['type' => 'number', 'exclusiveMaximum' => 1.5, 'exclusiveMinimum' => 0],
    ],
    'inclusive bounds' => [Schema::integer()->minimum(1)->maximum(10), ['type' => 'integer', 'maximum' => 10, 'minimum' => 1], ['type' => 'integer', 'maximum' => 10, 'minimum' => 1]],
]);

it('builds an object schema with named properties', function () {
    $id = Schema::integer('id')->format(SchemaFormat::INT64);
    $schema = Schema::object('User')
        ->description('A user')
        ->properties($id, Schema::string('email')->format('idn-email'), Schema::array('roles')->items(Schema::string())->uniqueItems())
        ->required($id, 'email')
        ->additionalProperties(false);

    expect($schema->toArray('3.1.0'))->toBe([
        'type' => 'object',
        'description' => 'A user',
        'properties' => [
            'id' => ['type' => 'integer', 'format' => 'int64'],
            'email' => ['type' => 'string', 'format' => 'idn-email'],
            'roles' => ['type' => 'array', 'items' => ['type' => 'string'], 'uniqueItems' => true],
        ],
        'required' => ['id', 'email'],
        'additionalProperties' => false,
    ]);
});

it('emits only the $ref of a reference', function () {
    expect(Schema::ref('#/components/schemas/User', 'author')->toArray('3.0.3'))->toBe(['$ref' => '#/components/schemas/User']);
});

it('rejects a required property schema without objectId', function () {
    Schema::object()->required(Schema::string());
})->throws(InvalidOpenapiDefinition::class, 'A required property schema has no objectId.');
