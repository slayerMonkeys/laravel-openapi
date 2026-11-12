<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

use BIWEB\LaravelOpenapi\OpenApi\Contracts\SchemaContract;

class MediaType extends BaseObject
{
    /** the key in content, not serialized */
    protected ?string $mediaType = null;

    protected ?SchemaContract $schema = null;

    protected mixed $example = null;

    /** @var list<Example>|null */
    protected ?array $examples = null;

    /** @var list<Encoding>|null */
    protected ?array $encoding = null;

    public static function json(?string $objectId = null): static
    {
        return static::create($objectId)->mediaType('application/json');
    }

    public static function xml(?string $objectId = null): static
    {
        return static::create($objectId)->mediaType('text/xml');
    }

    public static function plainText(?string $objectId = null): static
    {
        return static::create($objectId)->mediaType('text/plain');
    }

    public static function calendar(?string $objectId = null): static
    {
        return static::create($objectId)->mediaType('text/calendar');
    }

    public static function pdf(?string $objectId = null): static
    {
        return static::create($objectId)->mediaType('application/pdf');
    }

    public static function png(?string $objectId = null): static
    {
        return static::create($objectId)->mediaType('image/png');
    }

    public static function jpeg(?string $objectId = null): static
    {
        return static::create($objectId)->mediaType('image/jpeg');
    }

    public static function formUrlEncoded(?string $objectId = null): static
    {
        return static::create($objectId)->mediaType('application/x-www-form-urlencoded');
    }

    public static function formData(?string $objectId = null): static
    {
        return static::create($objectId)->mediaType('multipart/form-data');
    }

    public function mediaType(?string $mediaType): static
    {
        return $this->with('mediaType', $mediaType);
    }

    public function getMediaType(): ?string
    {
        return $this->mediaType;
    }

    public function schema(?SchemaContract $schema): static
    {
        return $this->with('schema', $schema);
    }

    public function example(mixed $example): static
    {
        return $this->with('example', $example);
    }

    public function examples(Example ...$examples): static
    {
        return $this->with('examples', $examples ?: null);
    }

    public function encoding(Encoding ...$encoding): static
    {
        return $this->with('encoding', $encoding ?: null);
    }

    protected function fields(string $version): array
    {
        return [
            'schema' => $this->schema,
            'example' => $this->example,
            'examples' => self::keyBy($this->examples, fn (Example $example) => $example->getObjectId()),
            'encoding' => self::keyBy($this->encoding, fn (Encoding $encoding) => $encoding->getObjectId()),
        ];
    }
}
