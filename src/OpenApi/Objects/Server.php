<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

class Server extends BaseObject
{
    protected const REQUIRED = ['url'];

    protected ?string $url = null;

    protected ?string $description = null;

    public function url(?string $url): static
    {
        return $this->with('url', $url);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }
}
