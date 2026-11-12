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

    /** @var list<Example>|null */
    protected ?array $examples = null;

    /** @var list<RequestBody>|null */
    protected ?array $requestBodies = null;

    /** @var list<Header>|null */
    protected ?array $headers = null;

    /** @var list<SecurityScheme>|null */
    protected ?array $securitySchemes = null;

    /** @var list<Link>|null */
    protected ?array $links = null;

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

    public function examples(Example ...$examples): static
    {
        return $this->with('examples', $examples ?: null);
    }

    public function requestBodies(RequestBody ...$requestBodies): static
    {
        return $this->with('requestBodies', $requestBodies ?: null);
    }

    public function headers(Header ...$headers): static
    {
        return $this->with('headers', $headers ?: null);
    }

    public function securitySchemes(SecurityScheme ...$securitySchemes): static
    {
        return $this->with('securitySchemes', $securitySchemes ?: null);
    }

    public function links(Link ...$links): static
    {
        return $this->with('links', $links ?: null);
    }

    protected function fields(string $version): array
    {
        return array_map(
            fn (?array $components) => self::keyBy($components, fn (SchemaContract|BaseObject $component) => $component->getObjectId()),
            parent::fields($version),
        );
    }
}
