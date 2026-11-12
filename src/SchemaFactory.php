<?php

namespace BIWEB\LaravelOpenapi;

use BIWEB\LaravelOpenapi\Attributes\Property;
use BIWEB\LaravelOpenapi\Attributes\Schema;
use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use BIWEB\LaravelOpenapi\OpenApi\Enums\SchemaType;
use BIWEB\LaravelOpenapi\OpenApi\Objects;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;

class SchemaFactory
{
    private const SCALARS = ['int' => SchemaType::INTEGER, 'float' => SchemaType::NUMBER, 'string' => SchemaType::STRING, 'bool' => SchemaType::BOOLEAN];

    /** @var array<string, Objects\Schema> */
    private array $schemas = [];

    /** Registers the #[Schema] class in components.schemas and returns its reference. */
    public function ref(string $class): Objects\Schema
    {
        $attribute = class_exists($class) ? ((new ReflectionClass($class))->getAttributes(Schema::class)[0] ?? null) : null;

        if ($attribute === null) {
            throw new InvalidOpenapiDefinition("Class [{$class}] is not annotated with #[Schema].");
        }

        $reflection = new ReflectionClass($class);
        $schema = $attribute->newInstance();
        $name = $schema->name ?? $reflection->getShortName();

        $this->schemas[$name] ??= $this->object($reflection, $schema)->objectId($name);

        return Objects\Schema::ref("#/components/schemas/{$name}");
    }

    /** @return array<string, Objects\Schema> keyed by component name, which is also each schema's objectId */
    public function schemas(): array
    {
        return $this->schemas;
    }

    /** @param  ReflectionClass<object>  $reflection */
    private function object(ReflectionClass $reflection, Schema $schema): Objects\Schema
    {
        $properties = [];
        $required = [];

        foreach ($this->fields($reflection) as $name => [$type, $override, $hasDefault]) {
            $nullable = $override->nullable ?? $type?->allowsNull() ?? false;

            if ($override->required ?? (! $nullable && ! $hasDefault)) {
                $required[] = $name;
            }

            $properties[] = $this->property("{$reflection->name}::\${$name}", $type, $override, $nullable)->objectId($name);
        }

        return Objects\Schema::object()
            ->description($schema->description)
            ->properties(...$properties)
            ->required(...$required);
    }

    /**
     * The fields of a JsonResource or a model are not PHP properties: once declared on the class, PHP properties are not read.
     *
     * @param  ReflectionClass<object>  $reflection
     * @return array<string, array{?ReflectionType, ?Property, bool}> type, override, has a default value
     */
    private function fields(ReflectionClass $reflection): array
    {
        $fields = [];

        foreach ($reflection->getAttributes(Property::class) as $attribute) {
            $declared = $attribute->newInstance();
            $name = $declared->name ?? throw new InvalidOpenapiDefinition("Class [{$reflection->name}] declares a #[Property] without a name.");
            $fields[$name] = [null, $declared, false];
        }

        if ($fields !== []) {
            return $fields;
        }

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (! $property->isStatic()) {
                $override = ($property->getAttributes(Property::class)[0] ?? null)?->newInstance();
                $fields[$property->name] = [$property->getType(), $override, $this->hasDefault($property)];
            }
        }

        return $fields;
    }

    private function property(string $label, ?ReflectionType $type, ?Property $override, bool $nullable): Objects\Schema
    {
        $deduced = $type instanceof ReflectionNamedType ? (self::SCALARS[$type->getName()] ?? null) : null;
        $openapiType = $override?->type === null ? $deduced : SchemaType::tryFrom($override->type)
            ?? throw new InvalidOpenapiDefinition("Property [{$label}] has an unknown type [{$override->type}].");

        return Objects\Schema::create()
            ->type($openapiType ?? throw new InvalidOpenapiDefinition("Property [{$label}] has no type a schema can be deduced from, set #[Property(type: …)]."))
            ->format($override?->format)
            ->description($override?->description)
            ->nullable($nullable ?: null)
            ->example($override?->example);
    }

    // a promoted property only carries its default on the constructor parameter
    private function hasDefault(ReflectionProperty $property): bool
    {
        if (! $property->isPromoted()) {
            return $property->hasDefaultValue();
        }

        foreach ($property->getDeclaringClass()->getConstructor()?->getParameters() ?? [] as $parameter) {
            if ($parameter->name === $property->name) {
                return $parameter->isDefaultValueAvailable();
            }
        }

        return false;
    }
}
