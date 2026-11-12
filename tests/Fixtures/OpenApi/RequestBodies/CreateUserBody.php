<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\RequestBodies;

use BIWEB\LaravelOpenapi\Definitions\RequestBody;
use BIWEB\LaravelOpenapi\OpenApi\Objects;
use BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\Schemas\UserSchema;

final class CreateUserBody extends RequestBody
{
    public function build(): Objects\RequestBody
    {
        return Objects\RequestBody::create()->required()->content(Objects\MediaType::json()->schema(Objects\Schema::ref(UserSchema::class)));
    }
}
