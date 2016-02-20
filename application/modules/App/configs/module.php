<?php

return [
    'middleware' => [
        //App\Middleware\Test::class,
    ],
    'router' => [
        'routes' => [
            'test' => [
                'pattern' => '/test',
                'handler' => 'App\Index@index'
            ]
        ]
    ],
    'dependencies' => [
        'services' => [
            App\Index::class => App\IndexFactory::class,
        ]
    ],
    'microloader' => [
        'files' => [
            App\Index::class => __DIR__ . '/../src/Index.php',
            App\IndexFactory::class => __DIR__ . '/../src/IndexFactory.php'
        ]
    ]
];