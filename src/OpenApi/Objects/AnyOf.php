<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

class AnyOf extends SchemaComposition
{
    protected function keyword(): string
    {
        return 'anyOf';
    }
}
