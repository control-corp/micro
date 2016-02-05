<?php

return [
    'db' => [
        'default' => 'localhost',
        'set_default_adapter' => false,
        'set_default_cache' => false,
        'adapters' => [
            'localhost' => [
                'adapter'  => 'mysqli',
                'host'     => 'localhost',
                'dbname'   => 'micro',
                'username' => 'root',
                'password' => '',
                'charset'  => 'utf8'
            ]
        ]
    ]
];