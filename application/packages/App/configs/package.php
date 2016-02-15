<?php

return [
    'middleware' => [
        App\Middleware\Test::class,
        App\Middleware\Test2::class,
        App\Middleware\Test3::class,
    ],
    'dependencies' => [
        'services' => [
            App\Index::class => App\IndexFactory::class
        ]
    ],
    'microloader' => [
        'files' => [
            App\Middleware\Test::class => __DIR__ . '/../src/Middleware/Test.php',
            App\Middleware\FirePHP\FirePHP::class => __DIR__ . '/../src/Middleware/FirePHP/FirePHP.php',
            App\Middleware\Test2::class => __DIR__ . '/../src/Middleware/Test2.php',
            App\Middleware\Test3::class => __DIR__ . '/../src/Middleware/Test3.php',
            App\Index::class => __DIR__ . '/../src/Index.php',
            App\IndexFactory::class => __DIR__ . '/../src/IndexFactory.php'
        ]
    ]
];