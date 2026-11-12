<?php

namespace BIWEB\LaravelOpenapi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Schema
{
    /** @param  string|null  $name  null means the short class name */
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
    ) {}
}
