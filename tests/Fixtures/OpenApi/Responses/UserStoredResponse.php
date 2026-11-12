<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\Responses;

use BIWEB\LaravelOpenapi\Definitions\Response;
use BIWEB\LaravelOpenapi\OpenApi\Objects;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Data\UserData;

final class UserStoredResponse extends Response
{
    public function build(): Objects\Response
    {
        return Objects\Response::created()->description('User created')->content(Objects\MediaType::json()->schema(Objects\Schema::ref(UserData::class)));
    }
}
