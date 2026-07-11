<?php

return [
    'default' => env('IMAP_DEFAULT_ACCOUNT', 'default'),

    'accounts' => [
        'default' => [
            'host' => env('IMAP_HOST', 'localhost'),
            'port' => (int) env('IMAP_PORT', 993),
            'protocol' => env('IMAP_PROTOCOL', 'imap'),
            'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
            'validate_cert' => filter_var(env('IMAP_VALIDATE_CERT', true), FILTER_VALIDATE_BOOL),
            'username' => env('IMAP_USERNAME'),
            'password' => env('IMAP_PASSWORD'),
            'authentication' => env('IMAP_AUTHENTICATION'),
            'timeout' => (int) env('IMAP_TIMEOUT', 30),
        ],
    ],

    'options' => [
        'debug' => filter_var(env('IMAP_DEBUG', false), FILTER_VALIDATE_BOOL),
    ],
];
