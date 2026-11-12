<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\Http;

use BIWEB\LaravelOpenapi\Attributes\Operation;
use BIWEB\LaravelOpenapi\Attributes\RequestBody;
use BIWEB\LaravelOpenapi\Attributes\Response;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Http\Resources\PlainResource;
use BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\RequestBodies\StoreUserBody;
use BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\Responses\UserCreatedResponse;

class ResponseController
{
    #[Operation]
    #[Response(200, 'Unwrapped', schema: PlainResource::class)]
    public function plain(): void {}

    #[Operation]
    #[Response(UserCreatedResponse::class, 'x')]
    public function definitionWithArguments(): void {}

    #[Operation]
    #[RequestBody(StoreUserBody::class, required: false)]
    public function bodyWithArguments(): void {}
}
