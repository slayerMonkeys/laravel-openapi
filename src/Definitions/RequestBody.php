<?php

namespace BIWEB\LaravelOpenapi\Definitions;

use BIWEB\LaravelOpenapi\OpenApi\Objects;

/** Resolved by the container: dependencies can be injected in the constructor. */
abstract class RequestBody
{
    abstract public function build(): Objects\RequestBody;
}
