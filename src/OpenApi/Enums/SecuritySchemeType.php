<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Enums;

enum SecuritySchemeType: string
{
    case API_KEY = 'apiKey';
    case HTTP = 'http';
    case OAUTH2 = 'oauth2';
    case OPEN_ID_CONNECT = 'openIdConnect';
}
