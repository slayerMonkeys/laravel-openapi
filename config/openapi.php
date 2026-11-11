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
        ],
    ],

];
