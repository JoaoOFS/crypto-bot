<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Swagger UI Assets Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Swagger UI Assets.
    |
    */

    'defaults' => [
        'paths' => [
            /*
             * Edit to set path where swagger ui assets should be stored
            */
            'swagger_ui_assets_path' => env('L5_SWAGGER_UI_ASSETS_PATH', 'vendor/swagger-api/swagger-ui/dist/'),
        ],
    ],
];
