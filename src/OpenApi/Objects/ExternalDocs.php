<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

class ExternalDocs extends BaseObject
{
    protected const REQUIRED = ['url'];

    protected ?string $description = null;

    protected ?string $url = null;

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function url(?string $url): static
    {
        return $this->with('url', $url);
    }
}
