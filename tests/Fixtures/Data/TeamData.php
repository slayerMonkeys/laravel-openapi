<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Data;

use BIWEB\LaravelOpenapi\Attributes\Property;
use BIWEB\LaravelOpenapi\Attributes\Schema;

#[Schema]
final class TeamData
{
    public function __construct(
        public UserData $owner,
        #[Property(items: UserData::class)] public array $members,
    ) {}
}
