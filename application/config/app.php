<?php

return [
    'config_cache_enabled' => false,
    'middleware' => [

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
            'shared' => 'application/resources',
        ]
    ],
    /* 'session' => [
        'name' => 'TEST',
        'save_path' => 'data/session'
    ], */
    'cache' => [
        'default'  => 'file',
        'adapters' => [
            'file' => [
                'frontend' => [
                    'adapter' => 'Core',
                    'options' => [
                        'lifetime' => (3600 * 24),
                        'automatic_serialization' => \true,
                    ],
                ],
                'backend' => [
                    'adapter' => 'File',
                    'options' => [
                        'cache_dir' => 'data/cache',
                    ],
                ],
            ],
        ],
    ],
    'translator' => [
        'adapter' => 'TranslatorArray',
        'options' => [
            'path' => 'data/languages'
        ]
    ],
];