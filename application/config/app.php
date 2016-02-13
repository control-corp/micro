<?php

return [
    'config_cache_enabled' => false,
    'middleware' => [
        //App\Middleware\Test::class,
        //App\Middleware\Test2::class,
        //App\Middleware\Test3::class,
    ],
    'micro_debug' => [
        'handlers' => [
            'dev_tools' => 1,
            'fire_php' => 1,
            //'performance' => 'data/classes.php'
        ],
    ],
    'router' => [
        'default_routes' => true,
    ],
    'acl' => [
        'enabled' => false
    ],
    'log' => [
        'enabled' => 0,
        'path' => __DIR__ . '/../../data/log',
    ],
    'error' => [
        'default' => 'App\Error@index',
    ],
    'view' => [
        'paths' => [
            'application/resources',
        ]
    ],
    /* 'session' => [
        'name' => 'TEST',
        'save_path' => 'data/session'
    ], */
    'translator' => [
        'adapter' => 'TranslatorArray',
        'options' => [
            'path' => 'data/languages'
        ]
    ],
];