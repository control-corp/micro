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
                'required' => 1,
                'class' => 'form-control'
            )
        ),
        'countryId' => array(
            'type' => 'select',
            'options' => array(
                'label' => 'Държава',
                'labelClass' => 'control-label',
                'required' => 1,
                'class' => 'form-control selectpicker',
                'emptyOption' => 'Избери',
                'multiOptions' => $nomCountries->fetchCachedPairs(null, null, array('name' => 'asc'))
            )
        ),
        'typeId' => array(
            'type' => 'select',
            'options' => array(
                'label' => 'Тип на марката',
                'labelClass' => 'control-label',
                'required' => 1,
                'class' => 'form-control',
                'emptyOption' => 'Избери',
                'multiOptions' => $nomTypes->fetchCachedPairs()
            )
        ),
        'statusId' => array(
            'type' => 'select',
            'options' => array(
                'label' => 'Статус на марката',
                'labelClass' => 'control-label',
                'class' => 'form-control',
                'emptyOption' => 'Избери',
                'multiOptions' => $nomStatuses->fetchCachedPairs()
            )
        ),
        'statusDate' => array(
            'type' => 'datepicker',
            'options' => array(
                'format' => 'd.m.Y',
                'label' => 'Дата за статуса',
                'labelClass' => 'control-label',
                'class' => 'datepicker form-control'
            )
        ),
        'statusNote' => array(
            'type' => 'textarea',
            'options' => array(
                'label' => 'Пояснение',
                'labelClass' => 'control-label',
                'class' => 'form-control',
                'attributes' => array('rows' => 5)
            )
        ),
        'notifierId' => array(
            'type' => 'select',
            'options' => array(
                'label' => 'Заявител',
                'labelClass' => 'control-label',
                'required' => 1,
                'class' => 'form-control',
                'emptyOption' => 'Избери',
                'multiOptions' => $nomNotifiers->fetchCachedPairs()
            )
        ),
        'classes' => array(
            'type' => 'select',
            'options' => array(
                'label' => 'Класове',
                'labelClass' => 'control-label',
                'required' => 1,
                'isArray' => 1,
                'class' => 'form-control',
                'multiOptions' => $nomClasses->fetchCachedPairs(),
                'attributes' => array(
                    'style' => 'height: 200px'
                )
            )
        ),
        'active' => array(
            'type' => 'checkbox',
            'options' => array(
                'label' => 'Активност',
                'labelClass' => 'control-label',
            )
        ),
        'requestNum' => array(
            'type' => 'text',
            'options' => array(
                'required' => 1,
                'label' => 'Номер на заяваване',
                'labelClass' => 'control-label',
                'class' => 'form-control'
            )
        ),
        'requestDate' => array(
            'type' => 'datepicker',
            'options' => array(
                'format' => 'd.m.Y',
                'required' => 1,
                'label' => 'Дата на заявяване',
                'labelClass' => 'control-label',
                'class' => 'datepicker form-control'
            )
        ),
        'registerNum' => array(
            'type' => 'text',
            'options' => array(
                'label' => 'Номер на регистрация',
                'labelClass' => 'control-label',
                'class' => 'form-control'
            )
        ),
        'registerDate' => array(
            'type' => 'datepicker',
            'options' => array(
                'format' => 'd.m.Y',
                'label' => 'Дата на регистрация',
                'labelClass' => 'control-label',
                'class' => 'datepicker form-control'
            )
        ),
        'description' => array(
            'type' => 'textarea',
            'options' => array(
                'label' => 'Информация',
                'labelClass' => 'control-label',
                'class' => 'form-control summernote',
                'attributes' => array(
                    'rows' => 5
                )
            )
        ),
        'price' => array(
            'type' => 'text',
            'options' => array(
                'label' => 'Цена',
                'labelClass' => 'control-label',
                'class' => 'form-control'
            )
        ),
        'priceDate' => array(
            'type' => 'datepicker',
            'options' => array(
                'format' => 'd.m.Y',
                'label' => 'Дата за цена',
                'labelClass' => 'control-label',
                'class' => 'datepicker form-control'
            )
        ),
        'priceComment' => array(
            'type' => 'text',
            'options' => array(
                'label' => 'Допълнителна информация',
                'labelClass' => 'control-label',
                'class' => 'form-control'
            )
        ),
        'btnSave'  => ['type' => 'submit', 'options' => ['value' => 'Запазване', 'class' => 'btn btn-primary']],
        'btnApply' => ['type' => 'submit', 'options' => ['value' => 'Прилагане', 'class' => 'btn btn-success']],
        'btnBack'  => ['type' => 'submit', 'options' => ['value' => 'Назад', 'class' => 'btn btn-default']],
    )
);