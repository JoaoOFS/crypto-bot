<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Swagger Constants Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Swagger Constants.
    |
    */

    'defaults' => [
        'constants' => [
            'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', 'http://localhost:8000/api/v1'),
        ],
    ],
];
