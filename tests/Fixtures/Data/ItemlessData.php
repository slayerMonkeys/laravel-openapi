<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Data;

use BIWEB\LaravelOpenapi\Attributes\Schema;

#[Schema]
final class ItemlessData
{
    public function __construct(public array $tags) {}
}
