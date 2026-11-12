<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

class Info extends BaseObject
{
    protected const REQUIRED = ['title', 'version'];

    protected ?string $title = null;

    protected ?string $summary = null;

    protected ?string $description = null;

    protected ?string $termsOfService = null;

    protected ?Contact $contact = null;

    protected ?License $license = null;

    protected ?string $version = null;

    public function title(?string $title): static
    {
        return $this->with('title', $title);
    }

    public function summary(?string $summary): static
    {
        return $this->with('summary', $summary);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function termsOfService(?string $termsOfService): static
    {
        return $this->with('termsOfService', $termsOfService);
    }

    public function contact(?Contact $contact): static
    {
        return $this->with('contact', $contact);
    }

    public function license(?License $license): static
    {
        return $this->with('license', $license);
    }

    public function version(?string $version): static
    {
        return $this->with('version', $version);
    }
}
