<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Swagger UI Display Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Swagger UI Display.
    |
    */

    'defaults' => [
        'ui' => [
            'display' => [
                /*
                 * Controls the default expansion setting for the operations and tags. It can be :
                 * 'list' (expands only the tags),
                 * 'full' (expands the tags and operations),
                 * 'none' (expands nothing).
                 */
                'doc_expansion' => env('L5_SWAGGER_UI_DOC_EXPANSION', 'none'),

                /**
                 * If set, enables filtering. The top bar will show an edit box that
                 * you can use to filter the tagged operations that are shown
                 */
                'filter' => true,

                /**
                 * If set, the request sample will be shown using the 'example' values by default.
                 */
                'requestSnippetsEnabled' => true,
            ],

            'authorization' => [
                /*
                 * If set to true, it persists authorization data, and it would not be lost on browser close/refresh
                 */
                'persist_authorization' => true,
            ],
        ],
    ],
];
