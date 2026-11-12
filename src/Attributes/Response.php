<?php

namespace BIWEB\LaravelOpenapi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Response
{
    /**
     * @param  int|string  $status  an HTTP status code, or a Response definition class, which forbids the other arguments
     * @param  class-string|null  $schema  a #[Schema] class or a Schema definition
     */
    public function __construct(
        public int|string $status,
        public string $description = '',
        public ?string $schema = null,
        public string $contentType = 'application/json',
        public bool $isList = false,
    ) {}
}
