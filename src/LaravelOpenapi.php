<?php

namespace BIWEB\LaravelOpenapi;

use BIWEB\LaravelOpenapi\Attributes\Operation;
use BIWEB\LaravelOpenapi\Attributes\Parameter;
use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use stdClass;

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

        return array_filter([
            'openapi' => $version,
            'info' => array_filter($config['info'] ?? [], fn ($value) => $value !== null),
            'servers' => $config['servers'] ?? [],
            // stdClass so an empty map encodes as {} instead of []
            'paths' => $this->paths($config['routes']['prefixes'] ?? []) ?: new stdClass,
        ]);
    }

    /**
     * @param  list<string>  $prefixes
     * @return array<string, array<string, array<string, mixed>>>
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

            // OpenAPI has no optional path segments: {id?} becomes {id}
            $path = '/'.ltrim(str_replace('?}', '}', $route->uri()), '/');

            foreach ($verbs as $verb) {
                $paths[$path][$verb] = $this->operation($operation, $source, $route);
            }
        }

        return $paths;
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
     * @return array<string, mixed>
     */
    private function operation(Operation $operation, ReflectionClass|ReflectionMethod $source, Route $route): array
    {
        preg_match_all('/\{(\w+)\??\}/', $route->uri(), $matches);

        // keyed by in + name, the pair OpenAPI requires to be unique, so an explicit #[Parameter] replaces the implicit one
        $parameters = [];

        foreach ($matches[1] as $name) {
            $parameters["path.{$name}"] = ['name' => $name, 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']];
        }

        foreach ($source->getAttributes(Parameter::class) as $attribute) {
            $parameter = $attribute->newInstance();
            $in = $parameter->in ?? (in_array($parameter->name, $matches[1], true) ? 'path' : 'query');

            $parameters["{$in}.{$parameter->name}"] = array_filter([
                'name' => $parameter->name,
                'in' => $in,
                'description' => $parameter->description,
                'required' => $in === 'path' || $parameter->required,
                'schema' => array_filter(['type' => $parameter->type, 'format' => $parameter->format]),
            ]);
        }

        return array_filter([
            'tags' => $operation->tags,
            'summary' => $operation->summary,
            'description' => $operation->description,
            'operationId' => $operation->operationId,
            'parameters' => array_values($parameters),
            'deprecated' => $operation->deprecated,
        ]) + array_filter(['security' => $operation->security], fn ($value) => $value !== null);
    }
}
