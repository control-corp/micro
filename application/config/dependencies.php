<?php

return [
    'dependencies' => [
        'services' => [
            App\Service\Test::class => App\Service\TestFactory::class,
        ],
        'aliases' => [
            'test' => App\Service\Test::class,
        ],
    ],
];