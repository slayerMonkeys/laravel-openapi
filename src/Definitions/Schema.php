<?php

namespace BIWEB\LaravelOpenapi\Definitions;

use BIWEB\LaravelOpenapi\OpenApi\Contracts\SchemaContract;
use BIWEB\LaravelOpenapi\OpenApi\Objects\BaseObject;

/** Resolved by the container: dependencies can be injected in the constructor. */
abstract class Schema
{
    abstract public function build(): BaseObject&SchemaContract;
}
