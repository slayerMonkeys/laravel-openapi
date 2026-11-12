<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\Schemas;

use BIWEB\LaravelOpenapi\Definitions\Schema;
use BIWEB\LaravelOpenapi\OpenApi\Objects;

final class TreeSchema extends Schema
{
    public function build(): Objects\Schema
    {
        return Objects\Schema::object()->properties(Objects\Schema::array('children')->items(Objects\Schema::ref(self::class)));
    }
}
