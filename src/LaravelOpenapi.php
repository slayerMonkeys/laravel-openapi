<?php

namespace BIWEB\LaravelOpenapi;

use BIWEB\LaravelOpenapi\Attributes\Operation;
use BIWEB\LaravelOpenapi\Attributes\Parameter;
use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use BIWEB\LaravelOpenapi\OpenApi\Enums\HttpMethod;
use BIWEB\LaravelOpenapi\OpenApi\Enums\ParameterIn;
use BIWEB\LaravelOpenapi\OpenApi\Enums\SchemaType;
use BIWEB\LaravelOpenapi\OpenApi\Objects;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

class LaravelOpenapi
{
    private const OPERATION_ATTRIBUTES = [Operation::class, Parameter::class];

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

        return Objects\OpenApi::create()
            ->openapi($version)
            ->info($this->info($config['info'] ?? []))
            ->servers(...array_map($this->server(...), $config['servers'] ?? []))
            ->paths(...$this->paths($config['routes']['prefixes'] ?? []))
            ->toArray($version);
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
    private function paths(array $prefixes): array
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

            if ($operation === null) {
                continue;
            }

            $verbs = array_diff(array_map('strtolower', $route->methods()), ['head']);

            if ($operation->operationId !== null && count($verbs) > 1) {
                throw new InvalidOpenapiDefinition("Operation [{$class}@{$method->name}] has an operationId but matches several HTTP methods.");
            }

            foreach (PathExpander::expand($route->uri()) as $path => $optional) {
                foreach ($verbs as $verb) {
                    $paths[$path][] = $this->operation($operation, $source, $route, $path, $optional)->method(HttpMethod::from($verb));
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
    private function operation(Operation $operation, ReflectionClass|ReflectionMethod $source, Route $route, string $path, array $optional): Objects\Operation
    {
        preg_match_all('/\{(\w+)\??\}/', $route->uri(), $uri);
        preg_match_all('/\{(\w+)\}/', $path, $variant);
        $label = $source instanceof ReflectionMethod ? "{$source->class}@{$source->name}" : $source->name;

        // keyed by in + name, the pair OpenAPI requires to be unique, so an explicit #[Parameter] replaces the implicit one
        $parameters = [];

        foreach ($variant[1] as $name) {
            $parameters["path.{$name}"] = Objects\Parameter::path()->name($name)->required()->schema(Objects\Schema::string());
        }

        foreach ($source->getAttributes(Parameter::class) as $attribute) {
            $parameter = $attribute->newInstance();
            $in = $parameter->in ?? (in_array($parameter->name, $uri[1], true) ? 'path' : 'query');

            // an optional path parameter only belongs to the variants that contain it
            if ($in === 'path' && in_array($parameter->name, $uri[1], true) && ! in_array($parameter->name, $variant[1], true)) {
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

        $object = Objects\Operation::create()
            ->tags(...$operation->tags)
            ->summary($operation->summary)
            ->description($operation->description)
            // each variant of an optional path needs its own operationId: listComments, listCommentsByComment
            ->operationId($operation->operationId === null ? null : $operation->operationId.implode('', array_map(fn (string $name) => 'By'.Str::studly($name), $optional)))
            ->parameters(...array_values($parameters))
            ->deprecated($operation->deprecated ?: null);

        return $operation->security === [] ? $object->noSecurity() : $object->security(...array_map(
            fn (array $requirement) => $this->securityRequirement($requirement, $label),
            $operation->security ?? [],
        ));
    }

    /** @param  array<string, list<string>>  $requirement */
    private function securityRequirement(array $requirement, string $label): Objects\SecurityRequirement
    {
        // shortcut: SecurityRequirement holds one scheme, so an AND of schemes is rejected, upgrade it when one is needed
        if (count($requirement) > 1) {
            throw new InvalidOpenapiDefinition("Operation [{$label}] has a security requirement with several schemes, which is not supported.");
        }

        $scheme = array_key_first($requirement);

        // an empty requirement ({}) makes the security optional
        return $scheme === null ? Objects\SecurityRequirement::create()
            : Objects\SecurityRequirement::create()->securityScheme($scheme)->scopes(...$requirement[$scheme]);
    }
}
