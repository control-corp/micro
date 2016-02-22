<?php

return array(
    'paginatorPlacement' => 'both',
    'paginatorAlways' => 0,
    'buttons' => array(
        'btnAdd' => array(
            'value' => 'Добавяне',
            'class' => 'btn btn-primary'
        ),
        'btnDelete' => array(
            'value' => 'Изтриване',
            'class' => 'btn btn-danger',
            'attributes' => array(
                'data-rel' => 'ids[]',
                'data-action' => app('router')->assemble(\null, ['action' => 'delete']),
                'data-confirm' => 'Сигурни ли сте, че искате да изтриете избраните записи?'
            )
        )
    ),
    'columns' => array(
        'ids' => array(
            'type' => 'checkbox',
            'options' => array(
                'sourceField' => 'id',
                'checkAll' => 1,
                'class' => 'text-center',
                'headClass' => 'text-center',
                'headStyle' => 'width: 3%'
            )
        ),
        'id' => array(
            'options' => array(
                'title' => '#',
                'sourceField' => 'id',
                'headStyle' => 'width: 5%'
            )
        ),
        'name' => array(
            'type' => 'href',
            'options' => array(
                'sourceField' => 'name',
                'sortable' => 1,
                'title' => 'Име',
                'params' => array(
                    'action' => 'edit',
                    'id' => ':id'
                )
            )
        ),
        'alias' => array(
            'options' => array(
                'title' => 'Псевдоним',
                'sourceField' => 'alias',
                'headStyle' => 'width: 5%'
            )
        ),
        'delete' => array(
            'options' => array(
                'text'   => ' ',
                'headStyle'  => 'width: 5%',
                'class'  => 'text-center',
                'viewScript' => 'admin/groups/grid-delete'
            )
        ),
    )
);