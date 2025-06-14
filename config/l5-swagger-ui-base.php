<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Swagger UI Base Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Swagger UI Base.
    |
    */

    'defaults' => [
        'paths' => [
            /*
             * Edit to set the api's base path
            */
            'base' => env('L5_SWAGGER_BASE_PATH', null),
        ],
    ],
];
