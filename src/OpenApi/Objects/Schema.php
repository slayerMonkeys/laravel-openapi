<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use BIWEB\LaravelOpenapi\OpenApi\Contracts\SchemaContract;
use BIWEB\LaravelOpenapi\OpenApi\Enums\SchemaFormat;
use BIWEB\LaravelOpenapi\OpenApi\Enums\SchemaType;

/** The only object whose output differs between 3.0 and 3.1: nullable and exclusiveMinimum/exclusiveMaximum. */
class Schema extends BaseObject implements SchemaContract
{
    protected ?SchemaType $type = null;

    /** a SchemaFormat, or any other format string (OpenAPI formats are open-ended) */
    protected SchemaFormat|string|null $format = null;

    protected ?string $title = null;

    protected ?string $description = null;

    protected ?bool $nullable = null;

    /** @var list<mixed>|null */
    protected ?array $enum = null;

    protected mixed $default = null;

    protected ?SchemaContract $items = null;

    protected ?int $maxItems = null;

    protected ?int $minItems = null;

    protected ?bool $uniqueItems = null;

    protected ?string $pattern = null;

    protected ?int $maxLength = null;

    protected ?int $minLength = null;

    protected int|float|null $maximum = null;

    protected int|float|null $exclusiveMaximum = null;

    protected int|float|null $minimum = null;

    protected int|float|null $exclusiveMinimum = null;

    protected int|float|null $multipleOf = null;

    /** @var list<SchemaContract>|null */
    protected ?array $properties = null;

    /** @var list<string>|null */
    protected ?array $required = null;

    protected SchemaContract|bool|null $additionalProperties = null;

    protected ?int $maxProperties = null;

    protected ?int $minProperties = null;

    protected ?Discriminator $discriminator = null;

    protected ?bool $readOnly = null;

    protected ?bool $writeOnly = null;

    protected ?Xml $xml = null;

    protected ?ExternalDocs $externalDocs = null;

    protected mixed $example = null;

    protected ?bool $deprecated = null;

    public static function array(?string $objectId = null): static
    {
        return static::create($objectId)->type(SchemaType::ARRAY);
    }

    public static function boolean(?string $objectId = null): static
    {
        return static::create($objectId)->type(SchemaType::BOOLEAN);
    }

    public static function integer(?string $objectId = null): static
    {
        return static::create($objectId)->type(SchemaType::INTEGER);
    }

    public static function number(?string $objectId = null): static
    {
        return static::create($objectId)->type(SchemaType::NUMBER);
    }

    public static function object(?string $objectId = null): static
    {
        return static::create($objectId)->type(SchemaType::OBJECT);
    }

    public static function string(?string $objectId = null): static
    {
        return static::create($objectId)->type(SchemaType::STRING);
    }

    public function type(?SchemaType $type): static
    {
        return $this->with('type', $type);
    }

    public function format(SchemaFormat|string|null $format): static
    {
        return $this->with('format', $format);
    }

    public function title(?string $title): static
    {
        return $this->with('title', $title);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    /** `nullable: true` in 3.0, `type: [T, "null"]` in 3.1. */
    public function nullable(?bool $nullable = true): static
    {
        return $this->with('nullable', $nullable);
    }

    public function enum(mixed ...$enum): static
    {
        return $this->with('enum', array_values($enum) ?: null);
    }

    public function default(mixed $default): static
    {
        return $this->with('default', $default);
    }

    public function items(?SchemaContract $items): static
    {
        return $this->with('items', $items);
    }

    public function maxItems(?int $maxItems): static
    {
        return $this->with('maxItems', $maxItems);
    }

    public function minItems(?int $minItems): static
    {
        return $this->with('minItems', $minItems);
    }

    public function uniqueItems(?bool $uniqueItems = true): static
    {
        return $this->with('uniqueItems', $uniqueItems);
    }

    public function pattern(?string $pattern): static
    {
        return $this->with('pattern', $pattern);
    }

    public function maxLength(?int $maxLength): static
    {
        return $this->with('maxLength', $maxLength);
    }

    public function minLength(?int $minLength): static
    {
        return $this->with('minLength', $minLength);
    }

    public function maximum(int|float|null $maximum): static
    {
        return $this->with('maximum', $maximum);
    }

    /** The value must be strictly lower than $exclusiveMaximum. */
    public function exclusiveMaximum(int|float|null $exclusiveMaximum): static
    {
        return $this->with('exclusiveMaximum', $exclusiveMaximum);
    }

    public function minimum(int|float|null $minimum): static
    {
        return $this->with('minimum', $minimum);
    }

    /** The value must be strictly greater than $exclusiveMinimum. */
    public function exclusiveMinimum(int|float|null $exclusiveMinimum): static
    {
        return $this->with('exclusiveMinimum', $exclusiveMinimum);
    }

    public function multipleOf(int|float|null $multipleOf): static
    {
        return $this->with('multipleOf', $multipleOf);
    }

    /** Each property is named by its objectId. */
    public function properties(SchemaContract ...$properties): static
    {
        return $this->with('properties', $properties ?: null);
    }

    public function required(string|SchemaContract ...$required): static
    {
        return $this->with('required', array_map(fn (string|SchemaContract $property) => is_string($property) ? $property
            : $property->getObjectId() ?? throw new InvalidOpenapiDefinition('A required property schema has no objectId.'), array_values($required)) ?: null);
    }

    public function additionalProperties(SchemaContract|bool|null $additionalProperties): static
    {
        return $this->with('additionalProperties', $additionalProperties);
    }

    public function maxProperties(?int $maxProperties): static
    {
        return $this->with('maxProperties', $maxProperties);
    }

    public function minProperties(?int $minProperties): static
    {
        return $this->with('minProperties', $minProperties);
    }

    public function discriminator(?Discriminator $discriminator): static
    {
        return $this->with('discriminator', $discriminator);
    }

    public function readOnly(?bool $readOnly = true): static
    {
        return $this->with('readOnly', $readOnly);
    }

    public function writeOnly(?bool $writeOnly = true): static
    {
        return $this->with('writeOnly', $writeOnly);
    }

    public function xml(?Xml $xml): static
    {
        return $this->with('xml', $xml);
    }

    public function externalDocs(?ExternalDocs $externalDocs): static
    {
        return $this->with('externalDocs', $externalDocs);
    }

    public function example(mixed $example): static
    {
        return $this->with('example', $example);
    }

    public function deprecated(?bool $deprecated = true): static
    {
        return $this->with('deprecated', $deprecated);
    }

    protected function fields(string $version): array
    {
        $fields = [
            ...parent::fields($version),
            'properties' => self::keyBy($this->properties, fn (SchemaContract $property) => $property->getObjectId()),
        ];

        if (str_starts_with($version, '3.0')) {
            // 3.0 (draft-04): exclusive bounds are booleans that turn minimum/maximum exclusive
            foreach (['minimum' => 'exclusiveMinimum', 'maximum' => 'exclusiveMaximum'] as $bound => $exclusive) {
                if ($this->{$exclusive} !== null) {
                    $fields[$bound] = $this->{$exclusive};
                    $fields[$exclusive] = true;
                }
            }

            return $fields;
        }

        $fields['nullable'] = null;

        if ($this->nullable && $this->type !== null) {
            $fields['type'] = [$this->type->value, 'null'];
            $fields['enum'] = $this->enum === null ? null : [...$this->enum, null];
        }

        return $fields;
    }
}
