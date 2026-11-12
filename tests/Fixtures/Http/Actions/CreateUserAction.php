<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Http\Actions;

use BIWEB\LaravelOpenapi\Attributes\Operation;
use BIWEB\LaravelOpenapi\Attributes\RequestBody;
use BIWEB\LaravelOpenapi\Attributes\Response;
use BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\RequestBodies\StoreUserBody;
use BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\Responses\UserStoredResponse;

// AccountController::store described with definition classes
#[Operation(summary: 'Create a user', operationId: 'createUser', tags: ['users'])]
#[RequestBody(StoreUserBody::class)]
#[Response(UserStoredResponse::class)]
#[Response(422, 'Validation error')]
final class CreateUserAction
{
    public function __invoke(): void {}
}
