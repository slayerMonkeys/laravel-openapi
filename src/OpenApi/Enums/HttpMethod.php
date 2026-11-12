<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Enums;

enum HttpMethod: string
{
    case GET = 'get';
    case PUT = 'put';
    case POST = 'post';
    case DELETE = 'delete';
    case OPTIONS = 'options';
    case HEAD = 'head';
    case PATCH = 'patch';
    case TRACE = 'trace';
}
