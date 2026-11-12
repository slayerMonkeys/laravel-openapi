<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

class Server extends BaseObject
{
    protected const REQUIRED = ['url'];

    protected ?string $url = null;

    protected ?string $description = null;

    /** @var list<ServerVariable>|null */
    protected ?array $variables = null;

    public function url(?string $url): static
    {
        return $this->with('url', $url);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function variables(ServerVariable ...$variables): static
    {
        return $this->with('variables', $variables ?: null);
    }

    protected function fields(string $version): array
    {
        return [
            ...parent::fields($version),
            'variables' => self::keyBy($this->variables, fn (ServerVariable $variable) => $variable->getObjectId()),
        ];
    }
}
