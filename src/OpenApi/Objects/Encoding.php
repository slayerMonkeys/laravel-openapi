<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

use BIWEB\LaravelOpenapi\OpenApi\Enums\Style;

/** Keyed by the name of the property it encodes (its objectId). */
class Encoding extends BaseObject
{
    /** one or several media types, comma-separated */
    protected ?string $contentType = null;

    /** @var list<Header>|null */
    protected ?array $headers = null;

    protected ?Style $style = null;

    protected ?bool $explode = null;

    protected ?bool $allowReserved = null;

    public function contentType(?string $contentType): static
    {
        return $this->with('contentType', $contentType);
    }

    public function headers(Header ...$headers): static
    {
        return $this->with('headers', $headers ?: null);
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

    protected function fields(string $version): array
    {
        return [
            ...parent::fields($version),
            'headers' => self::keyBy($this->headers, fn (Header $header) => $header->getObjectId()),
        ];
    }
}
