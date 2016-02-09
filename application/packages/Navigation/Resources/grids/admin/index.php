<?php

return array(
    'paginatorPlacement' => 'top',
    [
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
    ],
    'columns' => array(
        'ids' => array(
            'type' => 'checkbox',
            'options' => array(
                'sourceField' => 'id',
                'checkAll' => 1,
                'class' => 'text-center',
                'headClass' => 'text-center',
                'headStyle' => 'width: 3%',
            )
        ),
        'id' => array(
            'options' => array(
                'sourceField' => 'id',
                'title' => '#',
                'sortable' => 1,
                'class' => 'text-center',
                'headClass' => 'text-center',
                'headStyle' => 'width: 5%',
            )
        ),
        'items' => array(
            'type' => 'href',
            'options' => array(
                'title' => 'Елементи',
                'text' => ' ',
                'sourceField' => 'id',
                'headStyle' => 'width: 5%',
                'class' => 'text-center',
                'headClass' => 'text-center',
                'hrefClass' => 'glyphicon glyphicon-list',
                'params' => array(
                    'action' => 'items',
                    'menuId' => ':id'
                )
            )
        ),
        'name' => array(
            'type' => 'href',
            'options' => array(
                'sourceField' => 'name',
                'title'  => 'Име',
                'reset'  => 0,
                'sortable' => 1,
                'params' => array(
                    'action' => 'edit',
                    'id' => ':id'
                )
            )
        ),
        'alias' => array(
            'options' => array(
                'sourceField' => 'alias',
                'sortable' => 1,
                'title' => 'Псевдоним',
                'headStyle' => 'width: 15%'
            )
        ),
        'active' => array(
            'type' => 'boolean',
            'options' => array(
                'sourceField' => 'active',
                'sortable' => 1,
                'title' => 'Активност',
                'class' => 'text-center',
                'true' => '<span class="fa fa-check"></span>',
                'false' => '<span class="fa fa-ban"></span>',
                'headStyle' => 'width: 10%'
            )
        ),
        'delete' => array(
            'type' => 'href',
            'options' => array(
                'text'   => ' ',
                'headStyle'  => 'width: 5%',
                'class'    => 'text-center',
                'hrefClass' => 'remove glyphicon glyphicon-trash',
                'params' => array(
                    'action' => 'delete',
                    'id' => ':id'
                )
            )
        ),
    )
);