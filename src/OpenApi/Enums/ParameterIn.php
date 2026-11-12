<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Enums;

enum ParameterIn: string
{
    case QUERY = 'query';
    case HEADER = 'header';
    case PATH = 'path';
    case COOKIE = 'cookie';
}
