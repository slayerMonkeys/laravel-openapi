<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

use BIWEB\LaravelOpenapi\OpenApi\Contracts\SchemaContract;
use BIWEB\LaravelOpenapi\OpenApi\Enums\ParameterIn;
use BIWEB\LaravelOpenapi\OpenApi\Enums\Style;

class Parameter extends BaseObject
{
    protected const REQUIRED = ['name', 'in'];

    protected ?string $name = null;

    protected ?ParameterIn $in = null;

    protected ?string $description = null;

    protected ?bool $required = null;

    protected ?bool $deprecated = null;

    protected ?bool $allowEmptyValue = null;

    protected ?Style $style = null;

    protected ?bool $explode = null;

    protected ?bool $allowReserved = null;

    protected ?SchemaContract $schema = null;

    protected mixed $example = null;

    /** @var list<Example>|null */
    protected ?array $examples = null;

    /** @var list<MediaType>|null */
    protected ?array $content = null;

    public static function query(?string $objectId = null): static
    {
        return static::create($objectId)->in(ParameterIn::QUERY);
    }

    public static function header(?string $objectId = null): static
    {
        return static::create($objectId)->in(ParameterIn::HEADER);
    }

    public static function path(?string $objectId = null): static
    {
        return static::create($objectId)->in(ParameterIn::PATH);
    }

    public static function cookie(?string $objectId = null): static
    {
        return static::create($objectId)->in(ParameterIn::COOKIE);
    }

    public function name(?string $name): static
    {
        return $this->with('name', $name);
    }

    public function in(?ParameterIn $in): static
    {
        return $this->with('in', $in);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function required(?bool $required = true): static
    {
        return $this->with('required', $required);
    }

    public function deprecated(?bool $deprecated = true): static
    {
        return $this->with('deprecated', $deprecated);
    }

    public function allowEmptyValue(?bool $allowEmptyValue = true): static
    {
        return $this->with('allowEmptyValue', $allowEmptyValue);
    }

    public function style(?Style $style): static
    {
        return $this->with('style', $style);
    }

    public function explode(?bool $explode = true): static
    {
        return $this->with('explode', $explode);
    }

    public function allowReserved(?bool $allowReserved = true): static
    {
        return $this->with('allowReserved', $allowReserved);
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

    public function content(MediaType ...$content): static
    {
        return $this->with('content', $content ?: null);
    }

    protected function fields(string $version): array
    {
        return [
            ...parent::fields($version),
            'examples' => self::keyBy($this->examples, fn (Example $example) => $example->getObjectId()),
            'content' => self::keyBy($this->content, fn (MediaType $mediaType) => $mediaType->getMediaType()),
        ];
    }
}
