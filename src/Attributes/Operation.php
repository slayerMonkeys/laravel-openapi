<?php

namespace BIWEB\LaravelOpenapi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
final readonly class Operation
{
    /**
     * @param  list<string>  $tags
     * @param  list<array<string, list<string>>>|null  $security  null inherits the document's, [] disables it
     * @param  list<string>  $documents  empty means every document
     */
    public function __construct(
        public ?string $summary = null,
        public ?string $description = null,
        public ?string $operationId = null,
        public array $tags = [],
        public bool $deprecated = false,
        public ?array $security = null,
        public array $documents = [],
    ) {}
}
