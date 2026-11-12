<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Objects;

class AllOf extends SchemaComposition
{
    protected function keyword(): string
    {
        return 'allOf';
    }
}
