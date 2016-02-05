<?php

return [
    'router' => [
        'default_routes' => false,
    ],
    'acl' => [
        'enabled' => false
    ],
    'log' => [
        'enabled' => 1,
        'path' => 'data/log',
    ],
    'error' => [
        'default' => 'App\Error@index',
    ],
    'view' => [
        'paths' => [
            'application/resources',
        ]
    ],
    'session' => [
        'name' => 'TEST',
        'save_path' => 'data/session'
    ],
    'translator' => [
        'adapter' => 'TranslatorArray',
        'options' => [
            'path' => 'data/languages'
        ]
    ],
];