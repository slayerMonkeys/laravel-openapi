<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

class RequestBody extends BaseObject
{
    protected const REQUIRED = ['content'];

    protected ?string $description = null;

    /** @var list<MediaType>|null */
    protected ?array $content = null;

    protected ?bool $required = null;

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function content(MediaType ...$content): static
    {
        return $this->with('content', $content ?: null);
    }

    public function required(?bool $required = true): static
    {
        return $this->with('required', $required);
    }

    protected function fields(string $version): array
    {
        return [
            ...parent::fields($version),
            'content' => self::keyBy($this->content, fn (MediaType $mediaType) => $mediaType->getMediaType()),
        ];
    }
}
