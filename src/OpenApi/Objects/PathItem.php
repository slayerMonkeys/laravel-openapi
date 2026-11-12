<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

class PathItem extends BaseObject
{
    /** the key in paths, not serialized */
    protected ?string $route = null;

    protected ?string $summary = null;

    protected ?string $description = null;

    /** @var list<Operation>|null */
    protected ?array $operations = null;

    /** @var list<Server>|null */
    protected ?array $servers = null;

    /** @var list<Parameter>|null */
    protected ?array $parameters = null;

    public function route(?string $route): static
    {
        return $this->with('route', $route);
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function summary(?string $summary): static
    {
        return $this->with('summary', $summary);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function operations(Operation ...$operations): static
    {
        return $this->with('operations', $operations ?: null);
    }

    public function servers(Server ...$servers): static
    {
        return $this->with('servers', $servers ?: null);
    }

    public function parameters(Parameter ...$parameters): static
    {
        return $this->with('parameters', $parameters ?: null);
    }

    protected function fields(string $version): array
    {
        return [
            'summary' => $this->summary,
            'description' => $this->description,
            ...self::keyBy($this->operations, fn (Operation $operation) => $operation->getMethod()?->value) ?? [],
            'servers' => $this->servers,
            'parameters' => $this->parameters,
        ];
    }
}
