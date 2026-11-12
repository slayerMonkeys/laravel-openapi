<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

class SecurityRequirement extends BaseObject
{
    protected ?string $securityScheme = null;

    /** @var list<string> */
    protected array $scopes = [];

    public function securityScheme(?string $securityScheme): static
    {
        return $this->with('securityScheme', $securityScheme);
    }

    public function scopes(string ...$scopes): static
    {
        return $this->with('scopes', $scopes);
    }

    // without a scheme, {} makes the security optional
    protected function fields(string $version): array
    {
        return $this->securityScheme === null ? [] : [$this->securityScheme => $this->scopes];
    }
}
