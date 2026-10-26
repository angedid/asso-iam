<?php
return [
    /*'paths' => [
        'api/*',
        '/login',
        '/logout',
        '/sanctum/csrf-cookie',
    ],
    'supports_credentials' => true,*/

    'paths' => ['*'],
    /*
 * You can specify which origins are allowed to access your application.
 * An asterisk (*) allows all origins.
 */
    'allowed_origins' => ['*'],

// ... other settings

    /*
     * You can specify which methods are allowed. An asterisk (*) allows all methods.
     */
    'allowed_methods' => ['*'],

    /*
     * You can specify which headers are allowed. An asterisk (*) allows all headers.
     */
    'allowed_headers' => ['*'],

    'allowed_origins_patterns' => [],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,



];
