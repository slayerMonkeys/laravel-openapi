<?php

namespace BIWEB\LaravelOpenapi\Tests\Fixtures\OpenApi\Responses;

use BIWEB\LaravelOpenapi\Definitions\Response;
use BIWEB\LaravelOpenapi\OpenApi\Objects;

final class NoContentResponse extends Response
{
    public function build(): Objects\Response
    {
        return Objects\Response::create()->statusCode(204)->description('No content');
    }
}
