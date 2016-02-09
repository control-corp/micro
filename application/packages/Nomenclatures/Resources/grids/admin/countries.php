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
        'continentId' => array(
            'type' => 'pairs',
            'options' => array(
                'sourceField' => 'continentId',
                'title' => 'Континент',
                'callable' => array(new Nomenclatures\Model\Continents(), 'fetchCachedPairs'),
                'params' => [null, null, ['name' => 'asc']]
            )
        ),
        'ISO3166Code' => array(
            'options' => array(
                'sourceField' => 'ISO3166Code',
                'title' => 'Код',
                'headStyle' => 'width: 15%'
            )
        ),
        'population' => array(
            'options' => array(
                'sourceField' => 'population',
                'title' => 'Население на държавата',
                'sortable' => 1,
                'headStyle' => 'width: 15%'
            )
        ),
        'countBrands' => array(
            'options' => array(
                'sourceField' => 'countBrands',
                'title' => 'Брой марки в държавата',
                'sortable' => 1,
                'headStyle' => 'width: 15%'
            )
        ),
        'active' => array(
            'type' => 'boolean',
            'options' => array(
                'sourceField' => 'active',
                'headStyle' => 'width: 5%',
                'title' => 'Активност',
                'class' => 'text-center',
                'true' => '<span class="fa fa-check"></span>',
                'false' => '<span class="fa fa-ban"></span>',
            )
        ),
        'delete' => array(
            'type' => 'href',
            'options' => array(
                'text' => ' ',
                'class' => 'text-center',
                'headStyle' => 'width: 5%',
                'hrefClass' => 'remove glyphicon glyphicon-trash',
                'params' => array(
                    'action' => 'delete',
                    'id' => ':id'
                )
            )
        )
    )
);