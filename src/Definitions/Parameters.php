<?php

namespace BIWEB\LaravelOpenapi\Definitions;

use BIWEB\LaravelOpenapi\OpenApi\Objects\Parameter;

/** Resolved by the container: dependencies can be injected in the constructor. */
abstract class Parameters
{
    /** @return list<Parameter> */
    abstract public function build(): array;
}
