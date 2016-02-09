<?php

use Nomenclatures\Model;

$nomTypes = new Model\Types();
$nomNotifiers = new Model\Notifiers();
$nomStatuses = new Model\Statuses();
$nomClasses = new Model\BrandClasses();
$nomCountries = new Model\Countries();

return array(
    'elements' => array(
        'name' => array(
            'type' => 'text',
            'options' => array(
                'label' => 'Име',
                'labelClass' => 'control-label',
                'class' => 'form-control',
                'belongsTo' => 'filters',
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
                'multiOptions' => $nomCountries->fetchCachedPairs(null, null, array('name' => 'asc'))
            )
        ),
        'typeId' => array(
            'type' => 'select',
            'options' => array(
                'label' => 'Тип',
                'labelClass' => 'control-label',
                'class' => 'form-control',
                'emptyOption' => 'Избери',
                'belongsTo' => 'filters',
                'multiOptions' => $nomTypes->fetchCachedPairs()
            )
        ),
        'statusId' => array(
            'type' => 'select',
            'options' => array(
                'label' => 'Статус',
                'labelClass' => 'control-label',
                'class' => 'form-control',
                'emptyOption' => 'Избери',
                'belongsTo' => 'filters',
                'multiOptions' => $nomStatuses->fetchCachedPairs()
            )
        ),
        'notifierId' => array(
            'type' => 'select',
            'options' => array(
                'label' => 'Заявител',
                'labelClass' => 'control-label',
                'class' => 'form-control',
                'emptyOption' => 'Избери',
                'belongsTo' => 'filters',
                'multiOptions' => $nomNotifiers->fetchCachedPairs()
            )
        ),
        'classes' => array(
            'type' => 'select',
            'options' => array(
                'label' => 'Класове',
                'labelClass' => 'control-label',
                'class' => 'form-control',
                'emptyOption' => 'Избери',
                'belongsTo' => 'filters',
                'multiOptions' => $nomClasses->fetchCachedPairs()
            )
        ),
        'requestNum' => array(
            'type' => 'text',
            'options' => array(
                'belongsTo' => 'filters',
                'label' => 'Номер на заяваване',
                'labelClass' => 'control-label',
                'class' => 'form-control'
            )
        ),
        'registerNum' => array(
            'type' => 'text',
            'options' => array(
                'belongsTo' => 'filters',
                'label' => 'Номер на регистрация',
                'labelClass' => 'control-label',
                'class' => 'form-control'
            )
        ),
        'months' => array(
            'type' => 'text',
            'options' => array(
                'belongsTo' => 'filters',
                'label' => 'Брой месеци до изтичане',
                'labelClass' => 'control-label',
                'class' => 'form-control'
            )
        )
    )
);