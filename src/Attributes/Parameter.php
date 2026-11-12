<?php

namespace BIWEB\LaravelOpenapi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Parameter
{
    /**
     * @param  'query'|'header'|'path'|'cookie'|null  $in  null means path when the URI has a {name} segment, query otherwise
     */
    public function __construct(
        public string $name,
        public ?string $in = null,
        public string $type = 'string',
        public ?string $format = null,
        public bool $required = false,
        public ?string $description = null,
    ) {}
}
