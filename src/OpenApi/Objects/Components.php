<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

use BIWEB\LaravelOpenapi\OpenApi\Contracts\SchemaContract;

/** Each component is keyed by its objectId. */
class Components extends BaseObject
{
    /** @var list<SchemaContract>|null */
    protected ?array $schemas = null;

    /** @var list<Response>|null */
    protected ?array $responses = null;

    /** @var list<Parameter>|null */
    protected ?array $parameters = null;

    /** @var list<RequestBody>|null */
    protected ?array $requestBodies = null;

    public function schemas(SchemaContract ...$schemas): static
    {
        return $this->with('schemas', $schemas ?: null);
    }

    public function responses(Response ...$responses): static
    {
        return $this->with('responses', $responses ?: null);
    }

    public function parameters(Parameter ...$parameters): static
    {
        return $this->with('parameters', $parameters ?: null);
    }

    public function requestBodies(RequestBody ...$requestBodies): static
    {
        return $this->with('requestBodies', $requestBodies ?: null);
    }

    protected function fields(string $version): array
    {
        return array_map(
            fn (?array $components) => self::keyBy($components, fn (SchemaContract|BaseObject $component) => $component->getObjectId()),
            parent::fields($version),
        );
    }
}
