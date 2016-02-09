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
                'dbname'   => 'brands_micro',
                'username' => 'root',
                'password' => '',
                'charset'  => 'utf8'
            ]
        ]
    ]
];