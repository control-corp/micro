<?php

namespace Mail\Controller\Admin;

use Micro\Application\Controller\Crud;
use Mail\Model;
use Micro\Model\EntityInterface;
use Micro\Form\Form;

class Index extends Crud
{
    protected $model = Model\Templates::class;

    protected $scope = 'admin';

    /**
     * (non-PHPdoc)
     * @see \Micro\Application\Controller\Crud::postValidate()
     */
    protected function postValidate(Form $form, EntityInterface $item, array $data)
    {
        if (isset($data['description']) && $data['description']) {
            $test = strip_tags($data['description']);
            if (empty($test)) {
                $form->description->addError('Полето е задължително');
                $form->markAsError();
            }
        }

        if (isset($data['alias']) && $data['alias']) {
            $m = new Model\Table\Templates();
            $where = array('alias = ?' => $data['alias']);
            if ($item->getId()) {
                $where['id <> ?'] = $item->getId();
            }
            if ($m->fetchRow($where)) {
                $form->alias->addError('Псевдонимът се използва');
                $form->markAsError();
            }
        }
    }
}