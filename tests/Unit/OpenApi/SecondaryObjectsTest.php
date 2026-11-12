<?php

use BIWEB\LaravelOpenapi\OpenApi\Enums\Flow;
use BIWEB\LaravelOpenapi\OpenApi\Enums\ParameterIn;
use BIWEB\LaravelOpenapi\OpenApi\Enums\SecuritySchemeType;
use BIWEB\LaravelOpenapi\OpenApi\Enums\Style;
use BIWEB\LaravelOpenapi\OpenApi\Objects\AllOf;
use BIWEB\LaravelOpenapi\OpenApi\Objects\AnyOf;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Components;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Contact;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Discriminator;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Encoding;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Example;
use BIWEB\LaravelOpenapi\OpenApi\Objects\ExternalDocs;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Header;
use BIWEB\LaravelOpenapi\OpenApi\Objects\License;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Link;
use BIWEB\LaravelOpenapi\OpenApi\Objects\MediaType;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Not;
use BIWEB\LaravelOpenapi\OpenApi\Objects\OAuthFlow;
use BIWEB\LaravelOpenapi\OpenApi\Objects\OneOf;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Response;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Schema;
use BIWEB\LaravelOpenapi\OpenApi\Objects\SecurityRequirement;
use BIWEB\LaravelOpenapi\OpenApi\Objects\SecurityScheme;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Server;
use BIWEB\LaravelOpenapi\OpenApi\Objects\ServerVariable;
use BIWEB\LaravelOpenapi\OpenApi\Objects\Xml;

// [object without the last setter, the last setter, expected output] — same output in 3.0 and 3.1
dataset('secondary objects', fn () => [
    'Contact' => [
        Contact::create()->name('Support'),
        fn (Contact $contact) => $contact->url('https://example.com')->email('support@example.com'),
        ['name' => 'Support', 'url' => 'https://example.com', 'email' => 'support@example.com'],
    ],
    'License' => [
        License::create()->name('MIT'),
        fn (License $license) => $license->url('https://opensource.org/licenses/MIT'),
        ['name' => 'MIT', 'url' => 'https://opensource.org/licenses/MIT'],
    ],
    'ServerVariable' => [
        ServerVariable::create('region')->default('eu'),
        fn (ServerVariable $variable) => $variable->enum('eu', 'us')->description('Region'),
        ['enum' => ['eu', 'us'], 'default' => 'eu', 'description' => 'Region'],
    ],
    'Server variables' => [
        Server::create()->url('https://{region}.example.com'),
        fn (Server $server) => $server->variables(ServerVariable::create('region')->default('eu')),
        ['url' => 'https://{region}.example.com', 'variables' => ['region' => ['default' => 'eu']]],
    ],
    'ExternalDocs' => [
        ExternalDocs::create()->url('https://docs.example.com'),
        fn (ExternalDocs $docs) => $docs->description('Guide'),
        ['description' => 'Guide', 'url' => 'https://docs.example.com'],
    ],
    'Encoding' => [
        Encoding::create('avatar')->contentType('image/png, image/jpeg'),
        fn (Encoding $encoding) => $encoding->headers(Header::create('X-Rate-Limit')->schema(Schema::integer()))
            ->style(Style::FORM)->explode()->allowReserved(),
        ['contentType' => 'image/png, image/jpeg', 'headers' => ['X-Rate-Limit' => ['schema' => ['type' => 'integer']]],
            'style' => 'form', 'explode' => true, 'allowReserved' => true],
    ],
    'Header' => [
        Header::create('X-Rate-Limit')->description('Requests left'),
        fn (Header $header) => $header->required()->schema(Schema::integer())->examples(Example::create('low')->value(1)),
        ['description' => 'Requests left', 'required' => true, 'schema' => ['type' => 'integer'], 'examples' => ['low' => ['value' => 1]]],
    ],
    'Link' => [
        Link::create('author')->operationId('showUser'),
        fn (Link $link) => $link->description('The author')->server(Server::create()->url('https://api.example.com')),
        ['operationId' => 'showUser', 'description' => 'The author', 'server' => ['url' => 'https://api.example.com']],
    ],
    'Example' => [
        Example::create('jane')->summary('Jane'),
        fn (Example $example) => $example->description('A user')->value(['id' => 1]),
        ['summary' => 'Jane', 'description' => 'A user', 'value' => ['id' => 1]],
    ],
    'AllOf' => [
        AllOf::create(),
        fn (AllOf $allOf) => $allOf->schemas(Schema::ref('#/components/schemas/User'), Schema::object()),
        ['allOf' => [['$ref' => '#/components/schemas/User'], ['type' => 'object']]],
    ],
    'AnyOf' => [AnyOf::create(), fn (AnyOf $anyOf) => $anyOf->schemas(Schema::string(), Schema::integer()), ['anyOf' => [['type' => 'string'], ['type' => 'integer']]]],
    'OneOf' => [OneOf::create(), fn (OneOf $oneOf) => $oneOf->schemas(Schema::string(), Schema::integer()), ['oneOf' => [['type' => 'string'], ['type' => 'integer']]]],
    'Not' => [Not::create(), fn (Not $not) => $not->schema(Schema::string()), ['not' => ['type' => 'string']]],
    'Discriminator' => [
        Discriminator::create()->propertyName('kind'),
        fn (Discriminator $discriminator) => $discriminator->mapping(['cat' => '#/components/schemas/Cat']),
        ['propertyName' => 'kind', 'mapping' => ['cat' => '#/components/schemas/Cat']],
    ],
    'Xml' => [
        Xml::create()->name('user'),
        fn (Xml $xml) => $xml->namespace('https://example.com/ns')->prefix('ex')->attribute(false)->wrapped(),
        ['name' => 'user', 'namespace' => 'https://example.com/ns', 'prefix' => 'ex', 'attribute' => false, 'wrapped' => true],
    ],
    'SecurityScheme' => [
        SecurityScheme::create('apiKey')->type(SecuritySchemeType::API_KEY),
        fn (SecurityScheme $scheme) => $scheme->name('X-Api-Key')->in(ParameterIn::HEADER)->description('Key'),
        ['type' => 'apiKey', 'description' => 'Key', 'name' => 'X-Api-Key', 'in' => 'header'],
    ],
    'OAuthFlow' => [
        OAuthFlow::create()->flow(Flow::AUTHORIZATION_CODE)->authorizationUrl('https://example.com/authorize'),
        fn (OAuthFlow $flow) => $flow->tokenUrl('https://example.com/token')->scopes(['read' => 'Read access']),
        ['authorizationUrl' => 'https://example.com/authorize', 'tokenUrl' => 'https://example.com/token', 'scopes' => ['read' => 'Read access']],
    ],
    'Response headers and links' => [
        Response::ok(),
        fn (Response $response) => $response->headers(Header::create('X-Rate-Limit'))->links(Link::create('author')->operationId('showUser')),
        ['description' => 'OK', 'headers' => ['X-Rate-Limit' => new stdClass], 'links' => ['author' => ['operationId' => 'showUser']]],
    ],
    'MediaType examples and encoding' => [
        MediaType::formData(),
        fn (MediaType $mediaType) => $mediaType->examples(Example::create('png')->externalValue('https://example.com/a.png'))
            ->encoding(Encoding::create('avatar')->contentType('image/png')),
        ['examples' => ['png' => ['externalValue' => 'https://example.com/a.png']], 'encoding' => ['avatar' => ['contentType' => 'image/png']]],
    ],
    'Components' => [
        Components::create()->examples(Example::create('jane')->value('Jane')),
        fn (Components $components) => $components->headers(Header::create('X-Rate-Limit'))
            ->securitySchemes(SecurityScheme::oauth2('oauth')->flows(OAuthFlow::create()->flow(Flow::CLIENT_CREDENTIALS)->tokenUrl('https://example.com/token')))
            ->links(Link::create('author')->operationId('showUser')),
        [
            'examples' => ['jane' => ['value' => 'Jane']],
            'headers' => ['X-Rate-Limit' => new stdClass],
            'securitySchemes' => ['oauth' => ['type' => 'oauth2', 'flows' => ['clientCredentials' => ['tokenUrl' => 'https://example.com/token', 'scopes' => new stdClass]]]],
            'links' => ['author' => ['operationId' => 'showUser']],
        ],
    ],
]);

it('builds each secondary object without modifying the original', function (object $original, Closure $set, array $expected) {
    $before = serialize($original);

    expect(json_encode($set($original)->toArray('3.0.3')))->toBe(json_encode($expected))
        ->and(json_encode($set($original)->toArray('3.1.0')))->toBe(json_encode($expected))
        ->and(serialize($original))->toBe($before);
})->with('secondary objects');

it('references a security scheme by its objectId', function () {
    expect(SecurityRequirement::create()->securityScheme(SecurityScheme::oauth2('oauth'))->scopes('read')->toArray('3.1.0'))
        ->toBe(['oauth' => ['read']]);
});
