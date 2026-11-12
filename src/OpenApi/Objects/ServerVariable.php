<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

/** Keyed by its objectId in the server variables. */
class ServerVariable extends BaseObject
{
    protected const REQUIRED = ['default'];

    /** @var list<string>|null */
    protected ?array $enum = null;

    protected ?string $default = null;

    protected ?string $description = null;

    public function enum(string ...$enum): static
    {
        return $this->with('enum', array_values($enum) ?: null);
    }

    public function default(?string $default): static
    {
        return $this->with('default', $default);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }
}
