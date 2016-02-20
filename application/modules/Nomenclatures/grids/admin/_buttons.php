<?php

return [
    'btnAdd' => [
        'value' => 'Добавяне',
        'class' => 'btn btn-primary'
    ],
    'btnActivate' => [
        'value' => 'Активиране',
        'class' => 'btn btn-success',
        'attributes' => [
            'data-rel' => 'ids[]',
            'data-action' => app('router')->assemble(\null, ['action' => 'activate']),
            'data-confirm' => 'Сигурни ли сте, че искате да активирате избраните записи?'
        ]
    ],
    'btnDeactivate' => [
        'value' => 'Деактивиране',
        'class' => 'btn btn-warning',
        'attributes' => [
            'data-rel' => 'ids[]',
            'data-action' => app('router')->assemble(\null, ['action' => 'deactivate']),
            'data-confirm' => 'Сигурни ли сте, че искате да деактивирате избраните записи?'
        ]
    ],
    'btnDelete' => [
        'value' => 'Изтриване',
        'class' => 'btn btn-danger',
        'attributes' => [
            'data-rel' => 'ids[]',
            'data-action' => app('router')->assemble(\null, ['action' => 'delete']),
            'data-confirm' => 'Сигурни ли сте, че искате да изтриете избраните записи?'
        ]
    ]
];