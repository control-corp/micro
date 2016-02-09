<?php

if (!function_exists('toDate')) {
    function toDate($value, $format) {
        if (empty($value)) {
            return null;
        }
        $value = new \DateTime($value);
        return $value->format($format);
    }
}

return array(
    'paginatorPlacement' => 'both',
    'columns' => array(
        'countryId' => array(
            'options' => array(
                'sourceField' => 'countryId',
                'title' => 'Държава',
                'viewScript' => 'admin/reports/grid-country',
            )
        ),
        'name' => array(
            'type' => 'href',
            'options' => array(
                'sourceField' => 'name',
                'title'  => 'Име',
                'reset'  => 0,
                'params' => array(
                    'controller' => 'index',
                    'action' => 'edit',
                    'id' => ':id'
                )
            )
        ),
        'typeId' => array(
            'type' => 'pairs',
            'options' => array(
                'sourceField' => 'typeId',
                'title' => 'Тип на марката',
                'callable' => array(new Nomenclatures\Model\Types(), 'fetchCachedPairs'),
                'headStyle' => 'width: 10%',
            )
        ),
        'registerNum' => array(
            'options' => array(
                'sourceField' => 'registerNum',
                'title' => 'Номер на регистрация',
                'headStyle' => 'width: 10%',
            )
        ),
        'image' => array(
            'options' => array(
                'sourceField' => 'image',
                'title' => 'Снимка',
                'viewScript' => 'admin/reports/grid-image',
                'headStyle' => 'width: 10%',
            )
        ),
        'statusId' => array(
            'options' => array(
                'sourceField' => 'statusId',
                'title' => 'Статус на марката',
                'viewScript' => 'admin/index/grid-status',
                'headStyle' => 'width: 10%',
            )
        ),
        'statusNote' => array(
            'options' => array(
                'sourceField' => 'statusNote',
                'title' => 'Коментар',
                'headStyle' => 'width: 10%',
            )
        ),
        'description' => array(
            'options' => array(
                'sourceField' => 'description',
                'title' => 'Информация',
                'headStyle' => 'width: 10%',
            )
        )
    )
);