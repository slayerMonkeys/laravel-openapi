<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Data;

use BIWEB\LaravelOpenapi\Attributes\Schema;

#[Schema]
final class UnionData
{
    public int|string $id = 0;
}
