<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

class License extends BaseObject
{
    protected const REQUIRED = ['name'];

    protected ?string $name = null;

    protected ?string $identifier = null;

    protected ?string $url = null;

    public function name(?string $name): static
    {
        return $this->with('name', $name);
    }

    public function identifier(?string $identifier): static
    {
        return $this->with('identifier', $identifier);
    }

    public function url(?string $url): static
    {
        return $this->with('url', $url);
    }
}
