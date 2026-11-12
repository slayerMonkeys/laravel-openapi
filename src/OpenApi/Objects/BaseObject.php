<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

use BackedEnum;
use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use Closure;
use stdClass;

/**
 * An immutable OpenAPI object: each setter returns a modified clone. Objects describe an intent, serialized for a
 * given version by toArray().
 */
abstract class BaseObject
{
    /** @var list<string> fields that must be set before serialization */
    protected const REQUIRED = [];

    protected ?string $ref = null;

    /** @var array<string, mixed> */
    protected array $extensions = [];

    final public function __construct(protected ?string $objectId = null) {}

    public static function create(?string $objectId = null): static
    {
        return new static($objectId);
    }

    /** A `#/…` pointer, or a class-string resolved into a pointer at generation. */
    public static function ref(string $ref, ?string $objectId = null): static
    {
        return (new static($objectId))->with('ref', $ref);
    }

    public function objectId(?string $objectId): static
    {
        return $this->with('objectId', $objectId);
    }

    public function getObjectId(): ?string
    {
        return $this->objectId;
    }

    public function getRef(): ?string
    {
        return $this->ref;
    }

    /** Sets the `x-{key}` extension. */
    public function x(string $key, mixed $value): static
    {
        $clone = clone $this;
        $clone->extensions[str_starts_with($key, 'x-') ? $key : "x-{$key}"] = $value;

        return $clone;
    }

    /** @return array<string, mixed> */
    public function toArray(string $version): array
    {
        return (array) $this->serialize($version, '');
    }

    protected function with(string $property, mixed $value): static
    {
        $clone = clone $this;
        $clone->{$property} = $value;

        return $clone;
    }

    /**
     * The fields to serialize, in output order: null values are omitted, objects and enums are serialized recursively.
     *
     * @return array<string, mixed>
     */
    protected function fields(string $version): array
    {
        $fields = get_object_vars($this);
        unset($fields['objectId'], $fields['ref'], $fields['extensions']);

        return $fields;
    }

    /**
     * @template T of object
     *
     * @param  list<T>|null  $objects
     * @param  Closure(T): (int|string|null)  $key
     * @return array<int|string, T>|null
     */
    protected static function keyBy(?array $objects, Closure $key): ?array
    {
        if ($objects === null) {
            return null;
        }

        $map = [];

        foreach ($objects as $object) {
            $map[$key($object) ?? throw new InvalidOpenapiDefinition('A ['.$object::class.'] has no key to be listed in a map.')] = $object;
        }

        return $map;
    }

    /** @return array<string, mixed>|stdClass */
    private function serialize(string $version, string $path): array|stdClass
    {
        if ($this->ref !== null) {
            return str_starts_with($this->ref, '#')
                ? ['$ref' => $this->ref]
                : throw new InvalidOpenapiDefinition("Reference [{$this->ref}] at [{$path}] is not resolved.");
        }

        foreach (static::REQUIRED as $field) {
            if ($this->{$field} === null) {
                throw new InvalidOpenapiDefinition('['.ltrim("{$path}.{$field}", '.').'] is required.');
            }
        }

        $array = [];

        foreach ($this->fields($version) + $this->extensions as $key => $value) {
            if ($value !== null) {
                $array[$key] = self::normalize($value, $version, ltrim("{$path}.{$key}", '.'));
            }
        }

        // stdClass so an empty object encodes as {} instead of []
        return $array ?: new stdClass;
    }

    private static function normalize(mixed $value, string $version, string $path): mixed
    {
        if ($value instanceof self) {
            return $value->serialize($version, $path);
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = self::normalize($item, $version, "{$path}.{$key}");
            }
        }

        return $value;
    }
}
