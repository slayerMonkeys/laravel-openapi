<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Data;

use BIWEB\LaravelOpenapi\Attributes\Schema;

#[Schema]
final class TaskData
{
    public function __construct(public Priority $priority) {}
}
