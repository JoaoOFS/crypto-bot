<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Swagger UI Config Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Swagger UI Config.
    |
    */

    'defaults' => [
        'ui' => [
            'config' => [
                /*
                 * Configs plugin allows to fetch external configs instead of passing them to SwaggerUIBundle.
                 * See more at: https://github.com/swagger-api/swagger-ui#configs-plugin
                */
                'additional_config_url' => null,

                /*
                 * Apply a sort to the operation list of each API. It can be 'alpha' (sort by paths alphanumerically),
                 * 'method' (sort by HTTP method) or null (by default, the order is returned by the server).
                */
                'operations_sort' => env('L5_SWAGGER_OPERATIONS_SORT', null),

                /*
                 * Pass the validatorUrl parameter to SwaggerUi init on the JS side.
                 * A null value here disables validation.
                */
                'validator_url' => null,
            ],
        ],
    ],
];
