<?php

return array(
    'paginatorPlacement' => 'top',
    'buttons' => include __DIR__ . '/_buttons.php',
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
        'name' => array(
            'type' => 'href',
            'options' => array(
                'sourceField' => 'name',
                'sortable' => 1,
                'title'  => 'Име',
                'params' => array(
                    'action' => 'edit',
                    'id' => ':id'
                )
            )
        ),
        'code' => array(
            'options' => array(
                'sourceField' => 'code',
                'sortable' => 1,
                'title' => 'Код',
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