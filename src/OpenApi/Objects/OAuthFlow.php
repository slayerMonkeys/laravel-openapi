<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

use BIWEB\LaravelOpenapi\OpenApi\Enums\Flow;
use stdClass;

class OAuthFlow extends BaseObject
{
    /** the key in flows, not serialized */
    protected ?Flow $flow = null;

    protected ?string $authorizationUrl = null;

    protected ?string $tokenUrl = null;

    protected ?string $refreshUrl = null;

    /** @var array<string, string> */
    protected array $scopes = [];

    public function flow(?Flow $flow): static
    {
        return $this->with('flow', $flow);
    }

    public function getFlow(): ?Flow
    {
        return $this->flow;
    }

    public function authorizationUrl(?string $authorizationUrl): static
    {
        return $this->with('authorizationUrl', $authorizationUrl);
    }

    public function tokenUrl(?string $tokenUrl): static
    {
        return $this->with('tokenUrl', $tokenUrl);
    }

    public function refreshUrl(?string $refreshUrl): static
    {
        return $this->with('refreshUrl', $refreshUrl);
    }

    /** @param  array<string, string>  $scopes  scope => description */
    public function scopes(array $scopes): static
    {
        return $this->with('scopes', $scopes);
    }

    protected function fields(string $version): array
    {
        return [
            'authorizationUrl' => $this->authorizationUrl,
            'tokenUrl' => $this->tokenUrl,
            'refreshUrl' => $this->refreshUrl,
            // required, even empty
            'scopes' => $this->scopes ?: new stdClass,
        ];
    }
}
