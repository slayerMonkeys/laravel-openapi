<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Http\Actions;

use BIWEB\LaravelOpenapi\Attributes\Operation;
use BIWEB\LaravelOpenapi\Attributes\Parameter;

#[Operation(summary: 'Show a user', deprecated: true, security: [])]
#[Parameter(name: 'user', type: 'integer', format: 'int64')]
class ShowUserAction
{
    public function __invoke(): void {}
}
