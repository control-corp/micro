<?php

return [
    'elements' => [
        'name' => [
            'type' => 'text',
            'options' => [
                'label' => 'Име',
                'labelClass' => 'control-label',
                'class' => 'form-control',
                'required' => 1
            ]
        ],
        'alias' => [
            'type' => 'text',
            'options' => [
                'label' => 'Псевдоним',
                'labelClass' => 'control-label',
                'class' => 'form-control',
                'required' => 1
            ]
        ],
        'btnSave'  => ['type' => 'submit', 'options' => ['value' => 'Запазване', 'class' => 'btn btn-primary']],
        'btnApply' => ['type' => 'submit', 'options' => ['value' => 'Прилагане', 'class' => 'btn btn-success']],
        'btnBack'  => ['type' => 'submit', 'options' => ['value' => 'Назад', 'class' => 'btn btn-default']],
    ]
];