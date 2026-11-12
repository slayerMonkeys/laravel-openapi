<?php

namespace BIWEB\LaravelOpenapi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Property
{
    /**
     * @param  string|null  $name  required on a class (API Resource, model), ignored on a property
     * @param  bool|null  $nullable  null means deduced from the PHP type
     * @param  bool|null  $required  null means required when not nullable and without a default value
     */
    public function __construct(
        public ?string $name = null,
        public ?string $type = null,
        public ?string $format = null,
        public ?string $description = null,
        public ?bool $nullable = null,
        public ?bool $required = null,
        public mixed $example = null,
    ) {}
}
