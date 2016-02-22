<?php

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
        'alias' => array(
            'type' => 'text',
            'options' => array(
                'required' => 1,
                'label' => 'Псевдоним',
                'labelClass' => 'control-label',
                'class' => 'form-control'
            )
        ),
        'description' => array(
            'type' => 'textarea',
            'options' => array(
                'label' => 'Описание',
                'labelClass' => 'control-label',
                'required' => 1,
                'class' => 'form-control summernote',
                'attributes' => array(
                    'rows' => 10
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
        'btnSave'  => ['type' => 'submit', 'options' => ['value' => 'Запазване', 'class' => 'btn btn-primary']],
        'btnApply' => ['type' => 'submit', 'options' => ['value' => 'Прилагане', 'class' => 'btn btn-success']],
        'btnBack'  => ['type' => 'submit', 'options' => ['value' => 'Назад', 'class' => 'btn btn-default']],
    )
);