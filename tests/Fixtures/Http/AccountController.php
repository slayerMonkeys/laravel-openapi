<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Http;

use BIWEB\LaravelOpenapi\Attributes\Operation;
use BIWEB\LaravelOpenapi\Attributes\Parameter;
use BIWEB\LaravelOpenapi\Attributes\Parameters;
use BIWEB\LaravelOpenapi\Attributes\RequestBody;
use BIWEB\LaravelOpenapi\Attributes\Response;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Data\UserData;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Http\Resources\UserResource;
use BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\Parameters\PaginationParameters;

// the full document, described with attributes
class AccountController
{
    #[Operation(summary: 'List users', operationId: 'listUsers', tags: ['users'])]
    #[Parameters(PaginationParameters::class)]
    #[Parameter(name: 'search', description: 'Filter by email')]
    #[Response(200, 'Users', schema: UserResource::class, isList: true)]
    public function index(): void {}

    #[Operation(summary: 'Create a user', operationId: 'storeUser', tags: ['users'])]
    #[RequestBody(UserData::class)]
    #[Response(201, 'User created', schema: UserData::class)]
    #[Response(422, 'Validation error')]
    public function store(): void {}

    #[Operation(summary: 'Export users', operationId: 'exportUsers', tags: ['users'])]
    #[Response(200, 'Users', schema: UserData::class, isList: true)]
    public function export(): void {}

    #[Operation(summary: 'Show a user', operationId: 'showUser', tags: ['users'])]
    #[Parameter(name: 'user', type: 'integer')]
    #[Response(200, 'User', schema: UserResource::class)]
    #[Response(404, 'Not found')]
    public function show(): void {}

    #[Operation(summary: 'Delete a user', operationId: 'deleteUser', tags: ['users'])]
    #[Response(204, 'Deleted')]
    public function destroy(): void {}
}
