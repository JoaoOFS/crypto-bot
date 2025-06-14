<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Swagger Routes Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Swagger Routes.
    |
    */

    'defaults' => [
        'routes' => [
            /*
             * Route for accessing api documentation interface
            */
            'api' => 'api/documentation',

            /*
             * Route for accessing parsed swagger annotations.
            */
            'docs' => 'docs',

            /*
             * Route for Oauth2 authentication callback.
            */
            'oauth2_callback' => 'api/oauth2-callback',

            /*
             * Middleware allows to prevent unexpected access to API documentation
            */
            'middleware' => [
                'api' => [],
                'asset' => [],
                'docs' => [],
                'oauth2_callback' => [],
            ],
        ],
    ],
];
