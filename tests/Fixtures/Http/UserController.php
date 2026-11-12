<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Http;

use BIWEB\LaravelOpenapi\Attributes\Operation;
use BIWEB\LaravelOpenapi\Attributes\Parameter;

class UserController
{
    #[Operation(summary: 'List users', description: 'Paginated list.', tags: ['users'])]
    #[Parameter(name: 'page', type: 'integer', description: 'Page number')]
    #[Parameter(name: 'X-Tenant', in: 'header', required: true)]
    public function index(): void {}

    #[Operation(summary: 'Show a user', operationId: 'showUser', security: [['bearer' => []]])]
    public function show(): void {}

    #[Operation(summary: 'Update a user', deprecated: true, security: [])]
    #[Parameter(name: 'user', type: 'integer', format: 'int64')]
    public function update(): void {}

    #[Operation(summary: 'Show a team')]
    public function team(): void {}

    #[Operation(operationId: 'replaceUser')]
    public function replace(): void {}

    public function undocumented(): void {}
}
