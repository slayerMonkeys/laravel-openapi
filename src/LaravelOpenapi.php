<?php

namespace BIWEB\LaravelOpenapi;

use BIWEB\LaravelOpenapi\Attributes\Operation;
use BIWEB\LaravelOpenapi\Attributes\Parameter;
use BIWEB\LaravelOpenapi\Attributes\Parameters;
use BIWEB\LaravelOpenapi\Attributes\RequestBody;
use BIWEB\LaravelOpenapi\Attributes\Response;
use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use BIWEB\LaravelOpenapi\OpenApi\Enums\Flow;
use BIWEB\LaravelOpenapi\OpenApi\Enums\HttpMethod;
use BIWEB\LaravelOpenapi\OpenApi\Enums\ParameterIn;
use BIWEB\LaravelOpenapi\OpenApi\Enums\SchemaType;
use BIWEB\LaravelOpenapi\OpenApi\Enums\SecuritySchemeType;
use BIWEB\LaravelOpenapi\OpenApi\Objects;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Str;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

class LaravelOpenapi
{
    private const OPERATION_ATTRIBUTES = [Operation::class, Parameter::class, Parameters::class, RequestBody::class, Response::class];

    /** @return array<string, mixed> */
    public function generate(?string $document = null): array
    {
        $document ??= config('openapi.default');
        $config = config("openapi.documents.{$document}");

        if (! is_array($config)) {
            throw new InvalidOpenapiDefinition("Unknown OpenAPI document [{$document}].");
        }

        $version = (string) ($config['openapi'] ?? '');

        if (! preg_match('/^3\.[01]\.\d+$/', $version)) {
            throw new InvalidOpenapiDefinition("Unsupported OpenAPI version [{$version}] in document [{$document}], expected 3.0.x or 3.1.x.");
        }

        $components = new Components;

        $openapi = $components->resolve(Objects\OpenApi::create()
            ->openapi($version)
            ->info($this->info($config['info'] ?? []))
            ->servers(...array_map($this->server(...), $config['servers'] ?? []))
            ->paths(...$this->paths($document, $config['routes']['prefixes'] ?? [], $components))
            ->security(...array_map(fn (array $requirement) => $this->securityRequirement($requirement, "Document [{$document}]"), $config['security'] ?? [])));

        $schemes = array_map($this->securityScheme(...), array_keys($config['security_schemes'] ?? []), array_values($config['security_schemes'] ?? []));
        $objects = $schemes === [] ? $components->toObject() : ($components->toObject() ?? Objects\Components::create())->securitySchemes(...$schemes);

        return $openapi->components($objects)->toArray($version);
    }

    /** @param  array<string, mixed>  $scheme */
    private function securityScheme(string $name, array $scheme): Objects\SecurityScheme
    {
        $flows = [];

        foreach ($scheme['flows'] ?? [] as $flow => $config) {
            $flows[] = Objects\OAuthFlow::create()
                ->flow(Flow::tryFrom($flow) ?? throw new InvalidOpenapiDefinition("Security scheme [{$name}] has an unknown flow [{$flow}]."))
                ->authorizationUrl($config['authorizationUrl'] ?? null)
                ->tokenUrl($config['tokenUrl'] ?? null)
                ->refreshUrl($config['refreshUrl'] ?? null)
                ->scopes($config['scopes'] ?? []);
        }

        return Objects\SecurityScheme::create($name)
            ->type(SecuritySchemeType::tryFrom($scheme['type'] ?? '') ?? throw new InvalidOpenapiDefinition("Security scheme [{$name}] has an unknown type [".($scheme['type'] ?? '').'].'))
            ->description($scheme['description'] ?? null)
            ->name($scheme['name'] ?? null)
            ->in(isset($scheme['in']) ? ParameterIn::tryFrom($scheme['in']) ?? throw new InvalidOpenapiDefinition("Security scheme [{$name}] has an unknown location [{$scheme['in']}].") : null)
            ->scheme($scheme['scheme'] ?? null)
            ->bearerFormat($scheme['bearerFormat'] ?? null)
            ->flows(...$flows)
            ->openIdConnectUrl($scheme['openIdConnectUrl'] ?? null);
    }

    /** @param  array<string, mixed>  $info */
    private function info(array $info): Objects\Info
    {
        $contact = $info['contact'] ?? null;
        $license = $info['license'] ?? null;

        return Objects\Info::create()
            ->title($info['title'] ?? null)
            ->summary($info['summary'] ?? null)
            ->description($info['description'] ?? null)
            ->termsOfService($info['termsOfService'] ?? null)
            ->contact($contact === null ? null : Objects\Contact::create()->name($contact['name'] ?? null)->url($contact['url'] ?? null)->email($contact['email'] ?? null))
            ->license($license === null ? null : Objects\License::create()->name($license['name'] ?? null)->identifier($license['identifier'] ?? null)->url($license['url'] ?? null))
            ->version($info['version'] ?? null);
    }

    /** @param  array<string, mixed>  $server */
    private function server(array $server): Objects\Server
    {
        $variables = [];

        foreach ($server['variables'] ?? [] as $name => $variable) {
            $variables[] = Objects\ServerVariable::create($name)
                ->enum(...$variable['enum'] ?? [])
                ->default($variable['default'] ?? null)
                ->description($variable['description'] ?? null);
        }

        return Objects\Server::create()->url($server['url'] ?? null)->description($server['description'] ?? null)->variables(...$variables);
    }

    /**
     * @param  list<string>  $prefixes
     * @return list<Objects\PathItem>
     */
    private function paths(string $document, array $prefixes, Components $components): array
    {
        $paths = [];

        foreach (Router::getRoutes()->getRoutes() as $route) {
            $class = $route->getControllerClass();

            if ($class === null || ! $this->matchesPrefix($route->uri(), $prefixes)) {
                continue;
            }

            // same as the protected Route::getControllerMethod(): getActionMethod() returns the class name for an invokable action
            $method = new ReflectionMethod($class, Str::parseCallback($route->getAction('uses'))[1]);
            $source = $this->attributeSource($class, $method);
            $operation = ($source->getAttributes(Operation::class)[0] ?? null)?->newInstance();

            // an empty documents list means every document
            if ($operation === null || ($operation->documents !== [] && ! in_array($document, $operation->documents, true))) {
                continue;
            }

            $verbs = array_diff(array_map('strtolower', $route->methods()), ['head']);

            if ($operation->operationId !== null && count($verbs) > 1) {
                throw new InvalidOpenapiDefinition("Operation [{$class}@{$method->name}] has an operationId but matches several HTTP methods.");
            }

            foreach (PathExpander::expand($route->uri()) as $path => $optional) {
                foreach ($verbs as $verb) {
                    $paths[$path][] = $this->operation($operation, $source, $route, $path, $optional, $components)->method(HttpMethod::from($verb));
                }
            }
        }

        return array_map(
            fn (string $path, array $operations) => Objects\PathItem::create()->route($path)->operations(...$operations),
            array_keys($paths),
            array_values($paths),
        );
    }

    /**
     * An invokable action (ADR) may carry its operation attributes on the class instead of __invoke, never on both.
     *
     * @param  class-string  $class
     * @return ReflectionClass<object>|ReflectionMethod
     */
    private function attributeSource(string $class, ReflectionMethod $method): ReflectionClass|ReflectionMethod
    {
        if ($method->name !== '__invoke' || ! $this->hasOperationAttributes($reflection = new ReflectionClass($class))) {
            return $method;
        }

        if ($this->hasOperationAttributes($method)) {
            throw new InvalidOpenapiDefinition("Action [{$class}] has operation attributes on both the class and __invoke.");
        }

        return $reflection;
    }

    /** @param  ReflectionClass<object>|ReflectionMethod  $reflection */
    private function hasOperationAttributes(ReflectionClass|ReflectionMethod $reflection): bool
    {
        foreach (self::OPERATION_ATTRIBUTES as $attribute) {
            if ($reflection->getAttributes($attribute) !== []) {
                return true;
            }
        }

        return false;
    }

    /** @param  list<string>  $prefixes */
    private function matchesPrefix(string $uri, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            $prefix = trim($prefix, '/');

            if ($uri === $prefix || str_starts_with($uri, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  ReflectionClass<object>|ReflectionMethod  $source
     * @param  list<string>  $optional  the optional parameters present in this variant of the path
     */
    private function operation(Operation $operation, ReflectionClass|ReflectionMethod $source, Route $route, string $path, array $optional, Components $components): Objects\Operation
    {
        preg_match_all('/\{(\w+)\??\}/', $route->uri(), $uri);
        preg_match_all('/\{(\w+)\}/', $path, $variant);
        $label = $source instanceof ReflectionMethod ? "{$source->class}@{$source->name}" : $source->name;
        // an optional path parameter only belongs to the variants that contain it
        $absent = fn (string $in, string $name) => $in === 'path' && in_array($name, $uri[1], true) && ! in_array($name, $variant[1], true);

        // keyed by in + name, the pair OpenAPI requires to be unique, so an explicit #[Parameter] replaces the implicit one
        $parameters = [];

        foreach ($variant[1] as $name) {
            $parameters["path.{$name}"] = Objects\Parameter::path()->name($name)->required()->schema(Objects\Schema::string());
        }

        foreach ($source->getAttributes(Parameters::class) as $attribute) {
            foreach ($attribute->newInstance()->classes as $class) {
                foreach ($components->parameters($class) as $key => $parameter) {
                    if (! $absent(...explode('.', $key, 2))) {
                        $parameters[$key] = $parameter;
                    }
                }
            }
        }

        foreach ($source->getAttributes(Parameter::class) as $attribute) {
            $parameter = $attribute->newInstance();
            $in = $parameter->in ?? (in_array($parameter->name, $uri[1], true) ? 'path' : 'query');

            if ($absent($in, $parameter->name)) {
                continue;
            }

            $parameters["{$in}.{$parameter->name}"] = Objects\Parameter::create()
                ->name($parameter->name)
                ->in(ParameterIn::from($in))
                ->description($parameter->description)
                ->required(($in === 'path' || $parameter->required) ?: null)
                ->schema(Objects\Schema::create()
                    ->type(SchemaType::tryFrom($parameter->type) ?? throw new InvalidOpenapiDefinition("Parameter [{$parameter->name}] of [{$label}] has an unknown type [{$parameter->type}]."))
                    ->format($parameter->format));
        }

        $body = ($source->getAttributes(RequestBody::class)[0] ?? null)?->newInstance();

        $object = Objects\Operation::create()
            ->tags(...$operation->tags)
            ->summary($operation->summary)
            ->description($operation->description)
            // each variant of an optional path needs its own operationId: listComments, listCommentsByComment
            ->operationId($operation->operationId === null ? null : $operation->operationId.implode('', array_map(fn (string $name) => 'By'.Str::studly($name), $optional)))
            ->parameters(...array_values($parameters))
            ->requestBody($body === null ? null : $this->requestBody($body, $label))
            ->responses(...array_map(fn (ReflectionAttribute $attribute) => $this->response($attribute->newInstance(), $label), $source->getAttributes(Response::class)))
            ->deprecated($operation->deprecated ?: null);

        return $operation->security === [] ? $object->noSecurity() : $object->security(...array_map(
            fn (array $requirement) => $this->securityRequirement($requirement, "Operation [{$label}]"),
            $operation->security ?? [],
        ));
    }

    private function requestBody(RequestBody $body, string $label): Objects\RequestBody
    {
        if (is_subclass_of($body->body, Definitions\RequestBody::class)) {
            return $body->contentType === 'application/json' && $body->required && $body->description === null
                ? Objects\RequestBody::ref($body->body)
                : throw new InvalidOpenapiDefinition("Request body [{$body->body}] of [{$label}] is a definition, which forbids the other arguments of #[RequestBody].");
        }

        return Objects\RequestBody::create()
            ->description($body->description)
            ->content(Objects\MediaType::create()->mediaType($body->contentType)->schema(Objects\Schema::ref($body->body)))
            ->required($body->required ?: null);
    }

    private function response(Response $response, string $label): Objects\Response
    {
        if (is_string($response->status) && class_exists($response->status)) {
            return $response->description === '' && $response->schema === null && $response->contentType === 'application/json' && ! $response->isList
                ? Objects\Response::ref($response->status)
                : throw new InvalidOpenapiDefinition("Response [{$response->status}] of [{$label}] is a definition, which forbids the other arguments of #[Response].");
        }

        return Objects\Response::create()
            ->statusCode($response->status)
            ->description($response->description)
            ->content(...($response->schema === null ? [] : [
                Objects\MediaType::create()->mediaType($response->contentType)->schema($this->responseSchema($response->schema, $response->isList)),
            ]));
    }

    // a JsonResource is wrapped in its $wrap key (data by default), unless $wrap is null
    private function responseSchema(string $class, bool $isList): Objects\Schema
    {
        $schema = $isList ? Objects\Schema::array()->items(Objects\Schema::ref($class)) : Objects\Schema::ref($class);
        $wrap = is_subclass_of($class, JsonResource::class) ? $class::$wrap : null;

        return $wrap === null ? $schema : Objects\Schema::object()->properties($schema->objectId($wrap))->required($wrap);
    }

    /** @param  array<string, list<string>>  $requirement */
    private function securityRequirement(array $requirement, string $label): Objects\SecurityRequirement
    {
        // shortcut: SecurityRequirement holds one scheme, so an AND of schemes is rejected, upgrade it when one is needed
        if (count($requirement) > 1) {
            throw new InvalidOpenapiDefinition("{$label} has a security requirement with several schemes, which is not supported.");
        }

        $scheme = array_key_first($requirement);

        // an empty requirement ({}) makes the security optional
        return $scheme === null ? Objects\SecurityRequirement::create()
            : Objects\SecurityRequirement::create()->securityScheme($scheme)->scopes(...$requirement[$scheme]);
    }
}
