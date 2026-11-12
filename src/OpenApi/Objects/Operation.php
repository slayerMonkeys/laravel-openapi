<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

use BIWEB\LaravelOpenapi\OpenApi\Enums\HttpMethod;

class Operation extends BaseObject
{
    /** the key in the path item, not serialized */
    protected ?HttpMethod $method = null;

    /** @var list<string>|null */
    protected ?array $tags = null;

    protected ?string $summary = null;

    protected ?string $description = null;

    protected ?string $operationId = null;

    /** @var list<Parameter>|null */
    protected ?array $parameters = null;

    protected ?RequestBody $requestBody = null;

    /** @var list<Response>|null */
    protected ?array $responses = null;

    protected ?bool $deprecated = null;

    /** @var list<SecurityRequirement>|null an empty list overrides the global security with none */
    protected ?array $security = null;

    /** @var list<Server>|null */
    protected ?array $servers = null;

    public static function get(?string $objectId = null): static
    {
        return static::create($objectId)->method(HttpMethod::GET);
    }

    public static function put(?string $objectId = null): static
    {
        return static::create($objectId)->method(HttpMethod::PUT);
    }

    public static function post(?string $objectId = null): static
    {
        return static::create($objectId)->method(HttpMethod::POST);
    }

    public static function delete(?string $objectId = null): static
    {
        return static::create($objectId)->method(HttpMethod::DELETE);
    }

    public static function options(?string $objectId = null): static
    {
        return static::create($objectId)->method(HttpMethod::OPTIONS);
    }

    public static function head(?string $objectId = null): static
    {
        return static::create($objectId)->method(HttpMethod::HEAD);
    }

    public static function patch(?string $objectId = null): static
    {
        return static::create($objectId)->method(HttpMethod::PATCH);
    }

    public static function trace(?string $objectId = null): static
    {
        return static::create($objectId)->method(HttpMethod::TRACE);
    }

    public function method(?HttpMethod $method): static
    {
        return $this->with('method', $method);
    }

    public function getMethod(): ?HttpMethod
    {
        return $this->method;
    }

    public function tags(string|Tag ...$tags): static
    {
        return $this->with('tags', array_map(fn (string|Tag $tag) => $tag instanceof Tag ? $tag->getName() : $tag, $tags) ?: null);
    }

    public function summary(?string $summary): static
    {
        return $this->with('summary', $summary);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function operationId(?string $operationId): static
    {
        return $this->with('operationId', $operationId);
    }

    public function parameters(Parameter ...$parameters): static
    {
        return $this->with('parameters', $parameters ?: null);
    }

    public function requestBody(?RequestBody $requestBody): static
    {
        return $this->with('requestBody', $requestBody);
    }

    public function responses(Response ...$responses): static
    {
        return $this->with('responses', $responses ?: null);
    }

    public function deprecated(?bool $deprecated = true): static
    {
        return $this->with('deprecated', $deprecated);
    }

    public function security(SecurityRequirement ...$security): static
    {
        return $this->with('security', $security ?: null);
    }

    /** Emits `security: []`, which removes the global security from this operation. */
    public function noSecurity(): static
    {
        return $this->with('security', []);
    }

    public function servers(Server ...$servers): static
    {
        return $this->with('servers', $servers ?: null);
    }

    protected function fields(string $version): array
    {
        $fields = parent::fields($version);
        unset($fields['method']);

        return [
            ...$fields,
            'responses' => self::keyBy($this->responses, fn (Response $response) => $response->getStatusCode() ?? 'default'),
        ];
    }
}
