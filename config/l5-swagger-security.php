<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Swagger Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Swagger Security.
    |
    */

    'defaults' => [
        'securityDefinitions' => [
            'securitySchemes' => [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT',
                ],
            ],
            'security' => [
                [
                    'bearerAuth' => [],
                ],
            ],
        ],
    ],
];
