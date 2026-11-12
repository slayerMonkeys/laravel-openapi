<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

class OneOf extends SchemaComposition
{
    protected function keyword(): string
    {
        return 'oneOf';
    }
}
