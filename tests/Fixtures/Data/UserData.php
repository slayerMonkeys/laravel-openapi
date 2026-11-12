<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Data;

use BIWEB\LaravelOpenapi\Attributes\Property;
use BIWEB\LaravelOpenapi\Attributes\Schema;

#[Schema]
final class UserData
{
    public function __construct(
        public TeamData $team,
        public Status $status,
        public ?Status $previousStatus,
        #[Property(items: 'string')] public array $tags,
    ) {}
}
