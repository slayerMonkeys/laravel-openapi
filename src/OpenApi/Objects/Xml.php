<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

class Xml extends BaseObject
{
    protected ?string $name = null;

    protected ?string $namespace = null;

    protected ?string $prefix = null;

    protected ?bool $attribute = null;

    protected ?bool $wrapped = null;

    public function name(?string $name): static
    {
        return $this->with('name', $name);
    }

    public function namespace(?string $namespace): static
    {
        return $this->with('namespace', $namespace);
    }

    public function prefix(?string $prefix): static
    {
        return $this->with('prefix', $prefix);
    }

    public function attribute(?bool $attribute = true): static
    {
        return $this->with('attribute', $attribute);
    }

    public function wrapped(?bool $wrapped = true): static
    {
        return $this->with('wrapped', $wrapped);
    }
}
