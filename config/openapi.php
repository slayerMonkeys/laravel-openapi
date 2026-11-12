<?php

// config for BIWEB/LaravelOpenapi
return [

    'default' => 'default',

    'documents' => [
        'default' => [
            'openapi' => '3.1.0', // any 3.0.x or 3.1.x
            'info' => [
                'title' => 'API',
                'version' => '1.0.0',
                'description' => null,
            ],
            'servers' => [
                // ['url' => 'https://api.example.com'],
            ],
            // only routes whose URI starts with one of these prefixes are documented
            'routes' => [
                'prefixes' => ['api'],
            ],
            // emitted in components.securitySchemes, keyed by name
            'security_schemes' => [
                // 'bearer' => ['type' => 'http', 'scheme' => 'bearer'],
            ],
            'security' => [], // the global requirement, e.g. [['bearer' => []]]
            'output' => base_path('openapi.json'), // written by openapi:generate, a .yaml/.yml extension gives YAML
            // GET {uri} serves the output file as is, 404 until it is generated
            'route' => [
                'enabled' => false,
                'uri' => 'openapi/default.json',
                'middleware' => [],
            ],
        ],
    ],

];
