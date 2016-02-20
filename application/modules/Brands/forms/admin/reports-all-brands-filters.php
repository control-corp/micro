<?php

return array(
    'elements' => array(
        'brandId' => array(
            'type' => 'text',
            'options' => array(
                'label' => 'Марка',
                'labelClass' => 'control-label',
                'class' => 'typeahead form-control',
                'belongsTo' => 'filters',
                'attributes' => array("autocomplete" => "off"),
            )
        ),
        'continentId' => array(
            'type' => 'select',
            'options' => array(
                'label' => 'Континент',
                'labelClass' => 'control-label',
                'isArray' => 1,
                'class' => 'form-control selectpicker',
                'belongsTo' => 'filters',
                'multiOptions' => (new Nomenclatures\Model\Continents())->fetchCachedPairs(null, null, array('name' => 'asc')),
                'attributes' => ['id' => 'continentId'],
            )
        ),
        'countryId' => array(
            'type' => 'select',
            'options' => array(
                'label' => 'Държава',
                'labelClass' => 'control-label',
                'isArray' => 1,
                'class' => 'form-control selectpicker',
                'belongsTo' => 'filters',
                'attributes' => ['id' => 'countryId'],
            )
        ),
        'date' => array(
            'type' => 'datepicker',
            'options' => array(
                'format'    => 'd.m.Y',
                'value'     => date('d.m.Y'),
                'label'     => 'Дата',
                'labelClass' => 'control-label',
                'class'     => 'datepicker form-control',
                'belongsTo' => 'filters',
            )
        )
    )
);