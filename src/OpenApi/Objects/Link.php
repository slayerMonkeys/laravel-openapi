<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

/** Keyed by its objectId in links. */
class Link extends BaseObject
{
    protected ?string $operationRef = null;

    protected ?string $operationId = null;

    protected ?string $description = null;

    protected ?Server $server = null;

    public function operationRef(?string $operationRef): static
    {
        return $this->with('operationRef', $operationRef);
    }

    public function operationId(?string $operationId): static
    {
        return $this->with('operationId', $operationId);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function server(?Server $server): static
    {
        return $this->with('server', $server);
    }
}
