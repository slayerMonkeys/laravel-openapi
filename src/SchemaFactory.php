<?php

namespace BIWEB\LaravelOpenapi;

use BackedEnum;
use BIWEB\LaravelOpenapi\Attributes\Property;
use BIWEB\LaravelOpenapi\Attributes\Schema;
use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use BIWEB\LaravelOpenapi\OpenApi\Enums\SchemaType;
use BIWEB\LaravelOpenapi\OpenApi\Objects;
use ReflectionClass;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;

class SchemaFactory
{
    private const BUILTINS = ['int' => SchemaType::INTEGER, 'float' => SchemaType::NUMBER, 'string' => SchemaType::STRING, 'bool' => SchemaType::BOOLEAN, 'array' => SchemaType::ARRAY];

    /**
     * The schema of a #[Schema] class, whose objectId is its component name. Nested classes are left as class-string
     * references, resolved by Components.
     */
    public function build(string $class): Objects\Schema
    {
        $attribute = class_exists($class) ? ((new ReflectionClass($class))->getAttributes(Schema::class)[0] ?? null) : null;

        if ($attribute === null) {
            throw new InvalidOpenapiDefinition("Class [{$class}] is not annotated with #[Schema].");
        }

        $reflection = new ReflectionClass($class);
        $schema = $attribute->newInstance();

        return $this->object($reflection, $schema)->objectId($schema->name ?? $reflection->getShortName());
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
        $class = $override?->type === null && $type instanceof ReflectionNamedType && ! $type->isBuiltin() ? $type->getName() : null;

        if ($class !== null && ! is_subclass_of($class, BackedEnum::class)) {
            // shortcut: a $ref has no siblings, so a nullable reference is emitted as non-nullable, wrap it in a composition when needed
            return Objects\Schema::ref($class);
        }

        if ($class !== null) {
            $schema = Objects\Schema::create()
                ->type(self::BUILTINS[(string) (new ReflectionEnum($class))->getBackingType()])
                ->enum(...array_column($class::cases(), 'value'));
        } else {
            $deduced = $type instanceof ReflectionNamedType ? (self::BUILTINS[$type->getName()] ?? null) : null;
            $openapiType = $override?->type === null ? $deduced : SchemaType::tryFrom($override->type)
                ?? throw new InvalidOpenapiDefinition("Property [{$label}] has an unknown type [{$override->type}].");

            $schema = Objects\Schema::create()
                ->type($openapiType ?? throw new InvalidOpenapiDefinition("Property [{$label}] has no type a schema can be deduced from, set #[Property(type: …)]."));

            if ($openapiType === SchemaType::ARRAY) {
                $items = $override->items ?? throw new InvalidOpenapiDefinition("Property [{$label}] is an array without items, set #[Property(items: …)].");
                $schema = $schema->items(SchemaType::tryFrom($items) === null ? Objects\Schema::ref($items) : Objects\Schema::create()->type(SchemaType::from($items)));
            }
        }

        return $schema
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
