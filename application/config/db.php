<?php

return [
    'db' => [
        'default' => 'localhost',
        'set_default_adapter' => true,
        'set_default_cache' => true,
        'adapters' => [
            'localhost' => [
                'adapter'  => 'mysqli',
                'host'     => 'localhost',
                'dbname'   => 'brands',
                'username' => 'root',
                'password' => 'root',
                'charset'  => 'utf8'
            ]
        ]
    ]
];