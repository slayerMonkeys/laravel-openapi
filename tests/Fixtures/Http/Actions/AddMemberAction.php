<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Http\Actions;

use BIWEB\LaravelOpenapi\Attributes\Operation;
use BIWEB\LaravelOpenapi\Attributes\RequestBody;
use BIWEB\LaravelOpenapi\Attributes\Response;
use BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\RequestBodies\CreateUserBody;
use BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\Responses\UserCreatedResponse;

final class AddMemberAction
{
    #[Operation(summary: 'Add a team member', operationId: 'addMember', tags: ['teams'])]
    #[RequestBody(CreateUserBody::class)]
    #[Response(UserCreatedResponse::class)]
    public function __invoke(): void {}
}
