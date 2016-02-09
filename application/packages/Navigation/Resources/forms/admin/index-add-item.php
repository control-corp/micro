<?php

$routes      = array_keys(app('router')->getRoutes());
$routesPairs = array();

foreach ($routes as $route) {
    $routesPairs[$route] = 'route-' . strtolower($route);
}

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
                'required' => 0,
                'label' => 'Псевдоним',
                'labelClass' => 'control-label',
                'class' => 'form-control'
            )
        ),
        'active' => array(
            'type' => 'checkbox',
            'options' => array(
                'label' => 'Активност',
                'labelClass' => 'control-label',
            )
        ),
        'url' => array(
            'type' => 'text',
            'options' => array(
                'label' => 'Връзка',
                'labelClass' => 'control-label',
                'class' => 'form-control'
            )
        ),
        'route' => array(
            'type' => 'select',
            'options' => array(
                'label' => 'Път',
                'labelClass' => 'control-label',
                'emptyOption' => 'Избери',
                'multiOptions' => $routesPairs,
                'translate' => true,
                'class' => 'form-control',
                'attributes' => array(
                    'id' => 'route'
                )
            )
        ),
        'reset' => array(
            'type' => 'checkbox',
            'options' => array(
                'label' => 'Нулиране на параметрите',
                'labelClass' => 'control-label',
            )
        ),
        'qsa' => array(
            'type' => 'checkbox',
            'options' => array(
                'label' => 'Добавяне на QSA',
                'labelClass' => 'control-label',
            )
        ),
        'parentId' => array(
            'type' => 'select',
            'options' => array(
                'label' => 'Родител',
                'labelClass' => 'control-label',
                'class' => 'form-control',
                'emptyOption' => 'Без родител',
            )
        ),
        'btnSave'  => ['type' => 'submit', 'options' => ['value' => 'Запазване', 'class' => 'btn btn-primary']],
        'btnApply' => ['type' => 'submit', 'options' => ['value' => 'Прилагане', 'class' => 'btn btn-success']],
        'btnBack'  => ['type' => 'submit', 'options' => ['value' => 'Назад', 'class' => 'btn btn-default']],
    )
);