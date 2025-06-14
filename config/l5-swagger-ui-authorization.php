<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Swagger UI Authorization Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Swagger UI Authorization.
    |
    */

    'defaults' => [
        'ui' => [
            'authorization' => [
                /*
                 * If set to true, it persists authorization data, and it would not be lost on browser close/refresh
                 */
                'persist_authorization' => true,
            ],
        ],
    ],
];
