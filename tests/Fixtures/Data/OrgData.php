<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Data;

use BIWEB\LaravelOpenapi\Attributes\Property;
use BIWEB\LaravelOpenapi\Attributes\Schema;
use BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\Schemas\UserSchema;

#[Schema]
final class OrgData
{
    public function __construct(
        #[Property(items: UserSchema::class)] public array $members,
    ) {}
}
