<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

use stdClass;

class OpenApi extends BaseObject
{
    protected const REQUIRED = ['openapi', 'info'];

    protected ?string $openapi = null;

    protected ?Info $info = null;

    /** @var list<Server>|null */
    protected ?array $servers = null;

    /** @var list<PathItem>|null */
    protected ?array $paths = null;

    protected ?Components $components = null;

    /** @var list<SecurityRequirement>|null */
    protected ?array $security = null;

    /** @var list<Tag>|null */
    protected ?array $tags = null;

    protected ?ExternalDocs $externalDocs = null;

    public function openapi(?string $openapi): static
    {
        return $this->with('openapi', $openapi);
    }

    public function info(?Info $info): static
    {
        return $this->with('info', $info);
    }

    public function servers(Server ...$servers): static
    {
        return $this->with('servers', $servers ?: null);
    }

    public function paths(PathItem ...$paths): static
    {
        return $this->with('paths', $paths ?: null);
    }

    public function components(?Components $components): static
    {
        return $this->with('components', $components);
    }

    public function security(SecurityRequirement ...$security): static
    {
        return $this->with('security', $security ?: null);
    }

    public function tags(Tag ...$tags): static
    {
        return $this->with('tags', $tags ?: null);
    }

    public function externalDocs(?ExternalDocs $externalDocs): static
    {
        return $this->with('externalDocs', $externalDocs);
    }

    protected function fields(string $version): array
    {
        return [
            ...parent::fields($version),
            // required in 3.0: an empty map is emitted as {}
            'paths' => self::keyBy($this->paths, fn (PathItem $path) => $path->getRoute()) ?? new stdClass,
        ];
    }
}
