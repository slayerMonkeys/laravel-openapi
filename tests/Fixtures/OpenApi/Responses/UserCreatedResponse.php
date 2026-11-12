<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\Responses;

use BIWEB\LaravelOpenapi\Definitions\Response;
use BIWEB\LaravelOpenapi\Definitions\Reusable;
use BIWEB\LaravelOpenapi\OpenApi\Objects;
use BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\Schemas\UserSchema;

final class UserCreatedResponse extends Response implements Reusable
{
    public function build(): Objects\Response
    {
        return Objects\Response::created('UserCreated')
            ->description('User created')
            ->content(Objects\MediaType::json()->schema(Objects\Schema::ref(UserSchema::class)));
    }
}
