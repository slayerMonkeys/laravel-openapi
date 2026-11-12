<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

use BIWEB\LaravelOpenapi\OpenApi\Contracts\SchemaContract;

abstract class SchemaComposition extends BaseObject implements SchemaContract
{
    /** @var list<SchemaContract>|null */
    protected ?array $schemas = null;

    public function schemas(SchemaContract ...$schemas): static
    {
        return $this->with('schemas', $schemas ?: null);
    }

    /** The keyword the schemas are listed under: allOf, anyOf or oneOf. */
    abstract protected function keyword(): string;

    protected function fields(string $version): array
    {
        return [$this->keyword() => $this->schemas];
    }
}
