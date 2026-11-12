<?php

use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use BIWEB\LaravelOpenapi\OpenApi\Enums\Flow;
use BIWEB\LaravelOpenapi\OpenApi\Enums\SchemaFormat;
use BIWEB\LaravelOpenapi\OpenApi\Enums\SecuritySchemeType;
use BIWEB\LaravelOpenapi\OpenApi\Objects\AllOf;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Components;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Contact;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Discriminator;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Encoding;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Example;
use BIWEB\LaravelOpenapi\OpenApi\Objects\ExternalDocs;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Header;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Info;
use BIWEB\LaravelOpenapi\OpenApi\Objects\License;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Link;
use BIWEB\LaravelOpenapi\OpenApi\Objects\MediaType;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Not;
use BIWEB\LaravelOpenapi\OpenApi\Objects\OAuthFlow;
use BIWEB\LaravelOpenapi\OpenApi\Objects\OneOf;
use BIWEB\LaravelOpenapi\OpenApi\Objects\OpenApi;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Operation;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Parameter;
use BIWEB\LaravelOpenapi\OpenApi\Objects\PathItem;
use BIWEB\LaravelOpenapi\OpenApi\Objects\RequestBody;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Response;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Schema;
use BIWEB\LaravelOpenapi\OpenApi\Objects\SecurityRequirement;
use BIWEB\LaravelOpenapi\OpenApi\Objects\SecurityScheme;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Server;
use BIWEB\LaravelOpenapi\OpenApi\Objects\ServerVariable;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Tag;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Xml;

function document(string $version, Response ...$responses): OpenApi
{
    $user = Schema::ref('#/components/schemas/User');

    return OpenApi::create()
        ->openapi($version)
        ->info(Info::create()->title('API')->description('Users API')->version('1.0.0')
            ->contact(Contact::create()->name('Support')->email('support@example.com'))
            ->license(License::create()->name('MIT')->url('https://opensource.org/licenses/MIT')))
        ->servers(Server::create()->url('https://{region}.example.com')->variables(ServerVariable::create('region')->enum('eu', 'us')->default('eu')))
        ->paths(PathItem::create()->route('/users/{id}')->operations(
            Operation::get()->tags('users')->operationId('showUser')
                ->parameters(Parameter::path()->name('id')->required()->schema(Schema::integer()->format(SchemaFormat::INT64)))
                ->responses(...$responses ?: [
                    Response::ok()
                        ->headers(Header::create('X-Rate-Limit')->schema(Schema::integer()))
                        ->content(MediaType::json()->schema($user)->examples(Example::create('jane')->value(['id' => 1])))
                        ->links(Link::create('self')->operationId('showUser')),
                    Response::notFound(),
                ]),
            Operation::put()->noSecurity()
                ->externalDocs(ExternalDocs::create()->url('https://docs.example.com/update'))
                ->requestBody(RequestBody::create()->required()->content(
                    MediaType::formData()->schema(Schema::object()->properties(Schema::string('avatar')->format('binary')))
                        ->encoding(Encoding::create('avatar')->contentType('image/png')),
                ))
                ->responses(Response::ok()),
        ))
        ->components(Components::create()
            ->schemas(
                Schema::object('User')
                    ->properties(Schema::integer('id'), Schema::string('nickname')->nullable(), Schema::number('score')->exclusiveMinimum(0))
                    ->required('id')
                    ->xml(Xml::create()->name('user')),
                AllOf::create('Admin')->schemas($user, Schema::object()->properties(Schema::boolean('root'))),
                OneOf::create('Member')->schemas($user, Schema::ref('#/components/schemas/Admin')),
                Schema::object('Animal')->properties(Schema::string('kind'), Not::create('legs')->schema(Schema::string()))
                    ->discriminator(Discriminator::create()->propertyName('kind')->mapping(['user' => '#/components/schemas/User'])),
            )
            ->securitySchemes(
                SecurityScheme::create('bearer')->type(SecuritySchemeType::HTTP)->scheme('bearer')->bearerFormat('JWT'),
                SecurityScheme::oauth2('oauth')->flows(OAuthFlow::create()->flow(Flow::CLIENT_CREDENTIALS)->tokenUrl('https://example.com/token')->scopes(['read' => 'Read'])),
            ))
        ->security(SecurityRequirement::create()->securityScheme('bearer'))
        ->tags(Tag::create()->name('users')->externalDocs(ExternalDocs::create()->url('https://docs.example.com/users')))
        ->externalDocs(ExternalDocs::create()->url('https://docs.example.com'));
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

it('rejects a 3.1-only field in a 3.0 document', function (Closure $set, string $path) {
    $info = Info::create()->title('API')->version('1.0.0');

    expect($set($info)->toArray('3.1.0'))->toBeArray()
        ->and(fn () => OpenApi::create()->openapi('3.0.3')->info($set($info))->toArray('3.0.3'))
        ->toThrow(InvalidOpenapiDefinition::class, "[{$path}] is only available in OpenAPI 3.1, not in 3.0.3.");
})->with([
    'info summary' => [fn (Info $info) => $info->summary('Users'), 'info.summary'],
    'license identifier' => [fn (Info $info) => $info->license(License::create()->name('MIT')->identifier('MIT')), 'info.license.identifier'],
]);

it('rejects an unresolved class-string reference', function () {
    Schema::object()->properties(Schema::ref(stdClass::class, 'user'))->toArray('3.1.0');
})->throws(InvalidOpenapiDefinition::class, 'Reference [stdClass] at [properties.user] is not resolved.');

it('rejects an object listed in a map without key', function () {
    Components::create()->schemas(Schema::object())->toArray('3.1.0');
})->throws(InvalidOpenapiDefinition::class, 'has no key to be listed in a map.');
