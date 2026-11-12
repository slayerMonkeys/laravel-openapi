<?php

use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use BIWEB\LaravelOpenapi\OpenApi\Enums\SchemaFormat;
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

function document(string $version, Response ...$responses): OpenApi
{
    $user = Schema::ref('#/components/schemas/User');

    return OpenApi::create()
        ->openapi($version)
        ->info(Info::create()->title('API')->description('Users API')->version('1.0.0'))
        ->servers(Server::create()->url('https://api.example.com'))
        ->paths(PathItem::create()->route('/users/{id}')->operations(
            Operation::get()->tags('users')->operationId('showUser')
                ->parameters(Parameter::path()->name('id')->required()->schema(Schema::integer()->format(SchemaFormat::INT64)))
                ->responses(...$responses ?: [Response::ok()->content(MediaType::json()->schema($user)), Response::notFound()]),
            Operation::put()->noSecurity()
                ->requestBody(RequestBody::create()->required()->content(MediaType::json()->schema($user)))
                ->responses(Response::ok()),
        ))
        ->components(Components::create()->schemas(Schema::object('User')
            ->properties(Schema::integer('id'), Schema::string('nickname')->nullable(), Schema::number('score')->exclusiveMinimum(0))
            ->required('id')))
        ->security(SecurityRequirement::create()->securityScheme('bearer'))
        ->tags(Tag::create()->name('users'));
}

it('builds a complete valid document', function (string $version) {
    expect(document($version)->toArray($version))->toBeValidOpenapi();
})->with(['3.0.3', '3.1.0']);

it('names the path of a missing required field', function () {
    document('3.1.0', Response::create()->statusCode(200))->toArray('3.1.0');
})->throws(InvalidOpenapiDefinition::class, '[paths./users/{id}.get.responses.200.description] is required.');

it('names a missing required field at the root', function () {
    OpenApi::create()->openapi('3.1.0')->toArray('3.1.0');
})->throws(InvalidOpenapiDefinition::class, '[info] is required.');

it('rejects an unresolved class-string reference', function () {
    Schema::object()->properties(Schema::ref(stdClass::class, 'user'))->toArray('3.1.0');
})->throws(InvalidOpenapiDefinition::class, 'Reference [stdClass] at [properties.user] is not resolved.');

it('rejects an object listed in a map without key', function () {
    Components::create()->schemas(Schema::object())->toArray('3.1.0');
})->throws(InvalidOpenapiDefinition::class, 'has no key to be listed in a map.');
