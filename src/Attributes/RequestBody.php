<?php

namespace BIWEB\LaravelOpenapi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
final readonly class RequestBody
{
    /** @param  class-string  $body  a RequestBody definition, which forbids the other arguments, or a #[Schema] class or Schema definition */
    public function __construct(
        public string $body,
        public string $contentType = 'application/json',
        public bool $required = true,
        public ?string $description = null,
    ) {}
}
