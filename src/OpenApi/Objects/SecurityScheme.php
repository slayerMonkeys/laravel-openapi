<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

use BIWEB\LaravelOpenapi\OpenApi\Enums\ParameterIn;
use BIWEB\LaravelOpenapi\OpenApi\Enums\SecuritySchemeType;

/** Keyed by its objectId in securitySchemes, and referenced by it from a SecurityRequirement. */
class SecurityScheme extends BaseObject
{
    protected const REQUIRED = ['type'];

    protected ?SecuritySchemeType $type = null;

    protected ?string $description = null;

    protected ?string $name = null;

    protected ?ParameterIn $in = null;

    protected ?string $scheme = null;

    protected ?string $bearerFormat = null;

    /** @var list<OAuthFlow>|null */
    protected ?array $flows = null;

    protected ?string $openIdConnectUrl = null;

    public static function oauth2(?string $objectId = null): static
    {
        return static::create($objectId)->type(SecuritySchemeType::OAUTH2);
    }

    public function type(?SecuritySchemeType $type): static
    {
        return $this->with('type', $type);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function name(?string $name): static
    {
        return $this->with('name', $name);
    }

    public function in(?ParameterIn $in): static
    {
        return $this->with('in', $in);
    }

    public function scheme(?string $scheme): static
    {
        return $this->with('scheme', $scheme);
    }

    public function bearerFormat(?string $bearerFormat): static
    {
        return $this->with('bearerFormat', $bearerFormat);
    }

    public function flows(OAuthFlow ...$flows): static
    {
        return $this->with('flows', $flows ?: null);
    }

    public function openIdConnectUrl(?string $openIdConnectUrl): static
    {
        return $this->with('openIdConnectUrl', $openIdConnectUrl);
    }

    protected function fields(string $version): array
    {
        return [
            ...parent::fields($version),
            'flows' => self::keyBy($this->flows, fn (OAuthFlow $flow) => $flow->getFlow()?->value),
        ];
    }
}
