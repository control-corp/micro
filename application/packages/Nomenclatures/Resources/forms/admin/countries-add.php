<?php

return [
    'elements' => [
        'name' => [
            'type' => 'text',
            'options' => [
                'label' => 'Име',
                'required' => 1,
                'labelClass' => 'control-label',
                'class' => 'form-control',
            ]
        ],
        'continentId' => [
            'type' => 'select',
            'options' => [
                'label' => 'Континент',
                'required' => 1,
                'labelClass' => 'control-label',
                'class' => 'form-control',
                'emptyOption' => 'Избери',
                'multiOptions' => (new Nomenclatures\Model\Continents)->fetchCachedPairs(\null, \null, ['name' => 'asc']),
            ],
        ],
        'ISO3166Code' => [
            'type' => 'text',
            'options' => [
                'required' => 1,
                'label' => 'Код',
                'labelClass' => 'control-label',
                'class' => 'form-control',
                'attributes' => ['maxlength' => 2],
            ],
        ],
        'population' => [
            'type' => 'text',
            'options' => [
                'label' => 'Население на държавата',
                'labelClass' => 'control-label',
                'class' => 'form-control',
                'attributes' => ['maxlength' => 20],
            ],
        ],
        'color' => [
            'type' => 'text',
            'options' => [
                'label' => 'Цвят',
                'labelClass' => 'control-label',
                'class' => 'form-control color',
            ],
        ],
        'active' => [
            'type' => 'checkbox',
            'options' => [
                'label' => 'Активност',
            ],
        ],
        'btnSave'  => ['type' => 'submit', 'options' => ['value' => 'Запазване', 'class' => 'btn btn-primary']],
        'btnApply' => ['type' => 'submit', 'options' => ['value' => 'Прилагане', 'class' => 'btn btn-success']],
        'btnBack'  => ['type' => 'submit', 'options' => ['value' => 'Назад', 'class' => 'btn btn-default']],
    ]
];