<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\RequestBodies;

use BIWEB\LaravelOpenapi\Definitions\RequestBody;
use BIWEB\LaravelOpenapi\OpenApi\Objects;
use BIWEB\LaravelOpenapi\Tests\Fixtures\Data\UserData;

final class StoreUserBody extends RequestBody
{
    public function build(): Objects\RequestBody
    {
        return Objects\RequestBody::create()->content(Objects\MediaType::json()->schema(Objects\Schema::ref(UserData::class)))->required();
    }
}
