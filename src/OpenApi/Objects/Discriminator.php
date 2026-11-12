<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

class Discriminator extends BaseObject
{
    protected const REQUIRED = ['propertyName'];

    protected ?string $propertyName = null;

    /** @var array<string, string>|null */
    protected ?array $mapping = null;

    public function propertyName(?string $propertyName): static
    {
        return $this->with('propertyName', $propertyName);
    }

    /** @param  array<string, string>  $mapping  property value => schema name or $ref */
    public function mapping(array $mapping): static
    {
        return $this->with('mapping', $mapping ?: null);
    }
}
