<?php

namespace BIWEB\LaravelOpenapi;

use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use BIWEB\LaravelOpenapi\OpenApi\Contracts\SchemaContract;
use BIWEB\LaravelOpenapi\OpenApi\Objects;
use BIWEB\LaravelOpenapi\OpenApi\Objects\BaseObject;

/**
 * The components of one generation: a #[Schema] class or a Reusable definition becomes a component, registered once,
 * the first time a tree references it. Any other definition is inlined.
 */
class Components
{
    /** @var array<string, array<string, string>> the class owning each component name, by components field */
    private array $owners = [];

    /** @var array<string, array<string, BaseObject>> by components field, then name */
    private array $components = [];

    /** @var array<string, true> the inline definitions being resolved, to stop a cycle */
    private array $inlining = [];

    public function __construct(private readonly SchemaFactory $factory = new SchemaFactory) {}

    /**
     * Replaces the class-string references of the tree's descendants by component pointers or inlined objects.
     *
     * @template T of BaseObject
     *
     * @param  T  $object
     * @return T
     */
    public function resolve(BaseObject $object): BaseObject
    {
        return $object->resolveRefs($this->replace(...));
    }

    /** A #[Schema] class or a Definitions\Schema. */
    public function schema(string $class): BaseObject&SchemaContract
    {
        if (is_subclass_of($class, Definitions\Schema::class)) {
            return $this->reference($class, $this->definition($class, Definitions\Schema::class)->build(), 'schemas');
        }

        $schema = $this->factory->build($class);

        return Objects\Schema::ref($this->register('schemas', $class, (string) $schema->getObjectId(), $schema));
    }

    /** A Definitions\Response, under the status code of the built object. */
    public function response(string $class): Objects\Response
    {
        $response = $this->definition($class, Definitions\Response::class)->build();

        return $this->reference($class, $response, 'responses')->statusCode($response->getStatusCode());
    }

    public function requestBody(string $class): Objects\RequestBody
    {
        return $this->reference($class, $this->definition($class, Definitions\RequestBody::class)->build(), 'requestBodies');
    }

    /**
     * A Definitions\Parameters: one component per parameter, named by its objectId, otherwise by its name.
     *
     * @return list<Objects\Parameter>
     */
    public function parameters(string $class): array
    {
        return array_map(
            fn (Objects\Parameter $parameter) => $this->reference($class, $parameter, 'parameters', $parameter->getObjectId() ?? $parameter->getName()),
            $this->definition($class, Definitions\Parameters::class)->build(),
        );
    }

    /** The registered components, null when nothing is referenced. */
    public function toObject(): ?Objects\Components
    {
        $components = Objects\Components::create();

        foreach ($this->components as $field => $objects) {
            $components = $components->{$field}(...array_values($objects));
        }

        return $this->components === [] ? null : $components;
    }

    private function replace(BaseObject $object): BaseObject
    {
        $class = (string) $object->getRef();

        $resolved = match (true) {
            $object instanceof Objects\Schema => $this->schema($class),
            $object instanceof Objects\Response => $this->response($class),
            $object instanceof Objects\RequestBody => $this->requestBody($class),
            default => throw new InvalidOpenapiDefinition('Reference ['.$class.'] on a ['.$object::class.'] cannot be resolved.'),
        };

        // the objectId of the reference is its key where it is used, such as a property name
        return $resolved->objectId($object->getObjectId());
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $base
     * @return T
     */
    private function definition(string $class, string $base): object
    {
        $definition = is_subclass_of($class, $base) ? app($class) : null;

        return $definition instanceof $base ? $definition : throw new InvalidOpenapiDefinition("Class [{$class}] does not extend [{$base}].");
    }

    /**
     * The pointer to the component of a Reusable definition, otherwise the object inlined.
     *
     * @template T of BaseObject
     *
     * @param  T  $object
     * @return T
     */
    private function reference(string $class, BaseObject $object, string $field, ?string $name = null): BaseObject
    {
        if (is_subclass_of($class, Definitions\Reusable::class)) {
            return $object::ref($this->register($field, $class, $name ?? $object->getObjectId() ?? class_basename($class), $object));
        }

        if (isset($this->inlining[$class])) {
            throw new InvalidOpenapiDefinition("Definition [{$class}] references itself, which needs it to implement Reusable.");
        }

        $this->inlining[$class] = true;

        try {
            return $this->resolve($object);
        } finally {
            unset($this->inlining[$class]);
        }
    }

    private function register(string $field, string $class, string $name, BaseObject $object): string
    {
        $owner = $this->owners[$field][$name] ?? null;

        if ($owner !== null && $owner !== $class) {
            throw new InvalidOpenapiDefinition("Classes [{$owner}] and [{$class}] are both named [{$name}] in components.{$field}, rename one with #[Schema(name: …)] or an objectId.");
        }

        if ($owner === null) {
            // registered before resolving its own references, so a cycle stops on the pointer; the key keeps the discovery order
            $this->owners[$field][$name] = $class;
            $this->components[$field][$name] = $object;
            $this->components[$field][$name] = $this->resolve($object)->objectId($name);
        }

        return "#/components/{$field}/{$name}";
    }
}
