<?php

namespace BIWEB\LaravelOpenapi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Parameters
{
    /** @var list<class-string> Parameters definitions */
    public array $classes;

    // a promoted property cannot be variadic
    public function __construct(string ...$classes)
    {
        $this->classes = array_values($classes);
    }
}
