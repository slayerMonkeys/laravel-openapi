<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Enums;

enum SchemaType: string
{
    case ARRAY = 'array';
    case BOOLEAN = 'boolean';
    case INTEGER = 'integer';
    case NUMBER = 'number';
    case OBJECT = 'object';
    case STRING = 'string';
}
