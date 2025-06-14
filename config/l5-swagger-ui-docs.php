<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Swagger UI Docs Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Swagger UI Docs.
    |
    */

    'defaults' => [
        'paths' => [
            /*
             * Absolute path to location where parsed annotations will be stored
            */
            'docs' => storage_path('api-docs'),
        ],
    ],
];
