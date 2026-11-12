<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Enums;

enum SchemaFormat: string
{
    case INT32 = 'int32';
    case INT64 = 'int64';
    case FLOAT = 'float';
    case DOUBLE = 'double';
    case BYTE = 'byte';
    case BINARY = 'binary';
    case DATE = 'date';
    case DATE_TIME = 'date-time';
    case PASSWORD = 'password';
    case EMAIL = 'email';
    case UUID = 'uuid';
}
