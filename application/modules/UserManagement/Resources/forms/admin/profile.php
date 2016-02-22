<?php

return [
    'elements' => [
        'username' => [
            'type'    => 'text',
            'options' => [
                'required' => 1,
                'class' => 'form-control',
                'attributes' => ['placeholder' => 'username', 'readonly' => 'readonly']
            ]
        ],
        'password' => [
            'type'    => 'password',
            'options' => [
                'required' => 0,
                'class' => 'form-control',
                'attributes' => ['placeholder' => 'password', 'autocomplete' => 'off']
            ]
        ]
    ]
];