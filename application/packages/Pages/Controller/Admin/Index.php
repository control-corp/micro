<?php

namespace Pages\Controller\Admin;

use Pages\Model;
use Micro\Application\Controller\Crud;
use Micro\Form\Form;
use Micro\Model\EntityInterface;

class Index extends Crud
{
    protected $model = \Pages\Model\Pages::class;

    protected $scope = 'admin';

    /**
     * (non-PHPdoc)
     * @see \Light\Controller\Crud::postValidate()
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
            $m = new Model\Table\Pages();
            $where = array('alias = ?' => $data['alias']);
            if ($item['id']) {
                $where['id <> ?'] = $item['id'];
            }
            if ($m->fetchRow($where)) {
                $form->alias->addError('Псевдонимът се използва');
                $form->markAsError();
            }
        }
    }

    public function detailAction()
    {
        $id = $this->request->getParam('id');

        $item = $this->getModel()->find((int) $id);

        if (!$item instanceof Model\Entity\Page) {
            throw new \Exception('Страницата не съществува', 404);
        }

        return ['item' => $item];
    }
}