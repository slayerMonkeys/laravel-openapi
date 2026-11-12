<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

/** Keyed by its objectId in examples. */
class Example extends BaseObject
{
    protected ?string $summary = null;

    protected ?string $description = null;

    protected mixed $value = null;

    protected ?string $externalValue = null;

    public function summary(?string $summary): static
    {
        return $this->with('summary', $summary);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function value(mixed $value): static
    {
        return $this->with('value', $value);
    }

    public function externalValue(?string $externalValue): static
    {
        return $this->with('externalValue', $externalValue);
    }
}
