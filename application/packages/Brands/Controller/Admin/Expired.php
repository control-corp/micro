<?php

namespace Brands\Controller\Admin;

use Micro\Application\Controller\Crud;
use Micro\Http\Response;
use Micro\Form\Form;
use Micro\Database\Expr;

class Expired extends Crud
{
    protected $ipp = 30;

    protected $model = \Brands\Model\Brands::class;

    protected $scope = 'admin';

    /**
     * (non-PHPdoc)
     * @see \Light\Controller\Crud::init()
     */
    public function init()
    {
        parent::init();

        $nomStatuses = new \Nomenclatures\Model\Statuses();
        $this->view->assign('nomStatuses', $nomStatuses->fetchCachedPairs());

        $nomTypes = new \Nomenclatures\Model\Types();
        $this->view->assign('nomTypes', $nomTypes->fetchCachedPairs());

        $nomNotifiers = new \Nomenclatures\Model\Notifiers();
        $this->view->assign('nomNotifiers', $nomNotifiers->fetchCachedPairs());

        $nomClasses = new \Nomenclatures\Model\BrandClasses();
        $this->view->assign('nomClasses', $nomClasses->fetchCachedPairs());
        $this->view->assign('nomClassesCodes', $nomClasses->fetchCachedPairs(null, array('id', 'code')));
    }

    public function indexAction()
    {
        if (($response = parent::indexAction()) instanceof Response) {
            return $response;
        }

        $form = new Form(package_path('Brands', 'Resources/forms/admin/expired-filters.php'));

        $form->populate($this->view->filters);

        $this->view->assign('form', $form);
    }

    public function modifyModel(array $filters)
    {
        $now = date('Y-m-d');

        $this->getModel()->addWhere(new Expr('reNewDate IS NOT NULL AND reNewDate < DATE("' . $now . '")'));

        if ($this->request->getParam('orderField') === null) {

            $this->request->setParam('orderField', 'reNewDate');

            $this->request->setParam('orderDir', 'ASC');
        }
    }
}