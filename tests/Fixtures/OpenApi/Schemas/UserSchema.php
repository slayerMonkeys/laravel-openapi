<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\Schemas;

use BIWEB\LaravelOpenapi\Definitions\Reusable;
use BIWEB\LaravelOpenapi\Definitions\Schema;
use BIWEB\LaravelOpenapi\OpenApi\Objects;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Data\TeamData;

final class UserSchema extends Schema implements Reusable
{
    public function build(): Objects\Schema
    {
        return Objects\Schema::object('User')
            ->properties(Objects\Schema::integer('id'), Objects\Schema::ref(TeamData::class, 'team'))
            ->required('id');
    }
}
