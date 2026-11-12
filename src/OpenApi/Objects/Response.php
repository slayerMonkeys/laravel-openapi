<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

class Response extends BaseObject
{
    protected const REQUIRED = ['description'];

    /** the key in responses (null for default), not serialized */
    protected int|string|null $statusCode = null;

    protected ?string $description = null;

    /** @var list<Header>|null */
    protected ?array $headers = null;

    /** @var list<MediaType>|null */
    protected ?array $content = null;

    /** @var list<Link>|null */
    protected ?array $links = null;

    public static function ok(?string $objectId = null): static
    {
        return static::create($objectId)->statusCode(200)->description('OK');
    }

    public static function created(?string $objectId = null): static
    {
        return static::create($objectId)->statusCode(201)->description('Created');
    }

    public static function movedPermanently(?string $objectId = null): static
    {
        return static::create($objectId)->statusCode(301)->description('Moved Permanently');
    }

    public static function movedTemporarily(?string $objectId = null): static
    {
        return static::create($objectId)->statusCode(302)->description('Moved Temporarily');
    }

    public static function badRequest(?string $objectId = null): static
    {
        return static::create($objectId)->statusCode(400)->description('Bad Request');
    }

    public static function unauthorized(?string $objectId = null): static
    {
        return static::create($objectId)->statusCode(401)->description('Unauthorized');
    }

    public static function forbidden(?string $objectId = null): static
    {
        return static::create($objectId)->statusCode(403)->description('Forbidden');
    }

    public static function notFound(?string $objectId = null): static
    {
        return static::create($objectId)->statusCode(404)->description('Not Found');
    }

    public static function unprocessableEntity(?string $objectId = null): static
    {
        return static::create($objectId)->statusCode(422)->description('Unprocessable Entity');
    }

    public static function tooManyRequests(?string $objectId = null): static
    {
        return static::create($objectId)->statusCode(429)->description('Too Many Requests');
    }

    public static function internalServerError(?string $objectId = null): static
    {
        return static::create($objectId)->statusCode(500)->description('Internal Server Error');
    }

    public function statusCode(int|string|null $statusCode): static
    {
        return $this->with('statusCode', $statusCode);
    }

    public function getStatusCode(): int|string|null
    {
        return $this->statusCode;
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function headers(Header ...$headers): static
    {
        return $this->with('headers', $headers ?: null);
    }

    public function content(MediaType ...$content): static
    {
        return $this->with('content', $content ?: null);
    }

    public function links(Link ...$links): static
    {
        return $this->with('links', $links ?: null);
    }

    protected function fields(string $version): array
    {
        return [
            'description' => $this->description,
            'headers' => self::keyBy($this->headers, fn (Header $header) => $header->getObjectId()),
            'content' => self::keyBy($this->content, fn (MediaType $mediaType) => $mediaType->getMediaType()),
            'links' => self::keyBy($this->links, fn (Link $link) => $link->getObjectId()),
        ];
    }
}
