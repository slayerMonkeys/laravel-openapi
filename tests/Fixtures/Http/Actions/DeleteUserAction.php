<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Http\Actions;

use BIWEB\LaravelOpenapi\Attributes\Operation;
use BIWEB\LaravelOpenapi\Attributes\Parameter;

class DeleteUserAction
{
    #[Operation(summary: 'Delete a user', deprecated: true, security: [])]
    #[Parameter(name: 'user', type: 'integer', format: 'int64')]
    public function __invoke(): void {}
}
