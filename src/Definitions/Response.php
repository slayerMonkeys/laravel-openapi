<?php

namespace BIWEB\LaravelOpenapi\Definitions;

use BIWEB\LaravelOpenapi\OpenApi\Objects;

/** Resolved by the container: dependencies can be injected in the constructor. The status code is the built object's. */
abstract class Response
{
    abstract public function build(): Objects\Response;
}
