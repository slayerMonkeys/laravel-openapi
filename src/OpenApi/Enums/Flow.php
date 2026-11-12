<?php

namespace BIWEB\LaravelOpenapi\OpenApi\Enums;

enum Flow: string
{
    case IMPLICIT = 'implicit';
    case PASSWORD = 'password';
    case CLIENT_CREDENTIALS = 'clientCredentials';
    case AUTHORIZATION_CODE = 'authorizationCode';
}
