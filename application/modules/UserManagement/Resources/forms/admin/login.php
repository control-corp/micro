<?php

return [
    'elements' => [
        'username' => [
            'type'    => 'text',
            'options' => [
                'required' => 1,
                'class' => 'form-control',
                'attributes' => ['placeholder' => 'потребителско име']
            ]
        ],
        'password' => [
            'type'    => 'password',
            'options' => [
                'required' => 1,
                'class' => 'form-control',
                'attributes' => ['placeholder' => 'парола']
            ]
        ]
    ]
];