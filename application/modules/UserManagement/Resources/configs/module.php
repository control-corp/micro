<?php

return [
    'router' => [
        'routes' => [
            'admin-login' => [
                'pattern' => '/admin/login',
                'handler' => 'UserManagement\Controller\Admin\Index@login',
            ],
        ],
    ],
];