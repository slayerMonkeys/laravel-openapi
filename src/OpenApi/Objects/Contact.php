<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

class Contact extends BaseObject
{
    protected ?string $name = null;

    protected ?string $url = null;

    protected ?string $email = null;

    public function name(?string $name): static
    {
        return $this->with('name', $name);
    }

    public function url(?string $url): static
    {
        return $this->with('url', $url);
    }

    public function email(?string $email): static
    {
        return $this->with('email', $email);
    }
}
