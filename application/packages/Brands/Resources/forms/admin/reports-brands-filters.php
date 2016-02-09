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