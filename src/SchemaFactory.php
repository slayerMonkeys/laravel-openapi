<?php

namespace BIWEB\LaravelOpenapi;

use BIWEB\LaravelOpenapi\Attributes\Property;
use BIWEB\LaravelOpenapi\Attributes\Schema;
use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;

class SchemaFactory
{
    private const SCALARS = ['int' => 'integer', 'float' => 'number', 'string' => 'string', 'bool' => 'boolean'];

    /** @var array<string, array<string, mixed>> */
    private array $schemas = [];

    public function __construct(private readonly string $version) {}

    /**
     * Registers the #[Schema] class in components.schemas and returns its reference.
     *
     * @return array{'$ref': string}
     */
    public function ref(string $class): array
    {
        $attribute = class_exists($class) ? ((new ReflectionClass($class))->getAttributes(Schema::class)[0] ?? null) : null;

        if ($attribute === null) {
            throw new InvalidOpenapiDefinition("Class [{$class}] is not annotated with #[Schema].");
        }

        $reflection = new ReflectionClass($class);
        $schema = $attribute->newInstance();
        $name = $schema->name ?? $reflection->getShortName();

        $this->schemas[$name] ??= $this->object($reflection, $schema);

        return ['$ref' => "#/components/schemas/{$name}"];
    }

    /** @return array<string, array<string, mixed>> */
    public function schemas(): array
    {
        return $this->schemas;
    }

    /**
     * @param  ReflectionClass<object>  $reflection
     * @return array<string, mixed>
     */
    private function object(ReflectionClass $reflection, Schema $schema): array
    {
        $properties = [];
        $required = [];

        foreach ($this->fields($reflection) as $name => [$type, $override, $hasDefault]) {
            $nullable = $override->nullable ?? $type?->allowsNull() ?? false;

            if ($override->required ?? (! $nullable && ! $hasDefault)) {
                $required[] = $name;
            }

            $properties[$name] = $this->property("{$reflection->name}::\${$name}", $type, $override, $nullable);
        }

        return array_filter([
            'type' => 'object',
            'description' => $schema->description,
            'properties' => $properties,
            'required' => $required,
        ]);
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

    /** @return array<string, mixed> */
    private function property(string $label, ?ReflectionType $type, ?Property $override, bool $nullable): array
    {
        $deduced = $type instanceof ReflectionNamedType ? (self::SCALARS[$type->getName()] ?? null) : null;
        $openapiType = $override->type ?? $deduced
            ?? throw new InvalidOpenapiDefinition("Property [{$label}] has no type a schema can be deduced from, set #[Property(type: …)].");

        $is30 = str_starts_with($this->version, '3.0');

        return array_filter([
            'type' => $nullable && ! $is30 ? [$openapiType, 'null'] : $openapiType,
            'format' => $override?->format,
            'description' => $override?->description,
            'nullable' => $nullable && $is30 ? true : null,
            'example' => $override?->example,
        ], fn ($value) => $value !== null);
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
