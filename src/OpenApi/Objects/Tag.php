<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

class Tag extends BaseObject
{
    protected const REQUIRED = ['name'];

    protected ?string $name = null;

    protected ?string $description = null;

    protected ?ExternalDocs $externalDocs = null;

    public function name(?string $name): static
    {
        return $this->with('name', $name);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function externalDocs(?ExternalDocs $externalDocs): static
    {
        return $this->with('externalDocs', $externalDocs);
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
