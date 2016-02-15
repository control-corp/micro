<?php

namespace Navigation\Controller\Admin;

use Micro\Application\Controller\Crud;
use Micro\Form\Form;
use Navigation\Model;
use Navigation\Helper;
use Micro\Model\EntityInterface;
use Micro\Http\Response\RedirectResponse;
use Micro\Application\Utils;
use Micro\Http\Response\JsonResponse;

class Index extends Crud
{
    protected $model = Model\Menu::class;

    protected $scope = 'admin';

    /**
     * (non-PHPdoc)
     * @see \Micro\Application\Controller\Crud::postValidate()
     */
    protected function postValidate(Form $form, EntityInterface $item, array $data)
    {
        if (isset($data['alias']) && $data['alias']) {

            $m = new Model\Table\Menu();

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

    public function itemsAction()
    {
        $menuId = $this->request->getParam('menuId');

        $menu = $this->getModel()->find((int) $menuId);

        if ($menu === \null) {
            return new RedirectResponse(route(\null, array('action' => 'index', 'menuId' => \null)));
        }

        if ($this->request->isPost()) {
            if ($this->request->getPost('btnAdd')) {
                return new RedirectResponse(route(\null, array('action' => 'add-item')));
            } else if ($this->request->getPost('btnBack')) {
                return new RedirectResponse(route(\null, array('action' => 'index', 'menuId' => \null)));
            }
        }

        $tree = new Helper\Tree($menu->getAlias());
        $items = $tree->getTree(\null);

        $this->view->assign('menu', $menu);
        $this->view->assign('items', $items);

    }

    public function addItemAction()
    {
        $menuId = $this->request->getParam('menuId');
        $id = $this->request->getParam('id');

        $menu = $this->getModel()->find((int) $menuId);

        if ($menu === null) {
            return new RedirectResponse(route(\null, array('action' => 'index', 'menuId' => \null)));
        }

        $model = new Model\Items();

        if ($id) {
            $item = $model->find((int) $id);
            if ($item === null) {
                return new RedirectResponse(route(\null, array('action' => 'items', 'id' => \null)));
            }
        } else {
            $item = $model->createEntity();
        }

        if ($item instanceof Model\Entity\Item) {

        }

        $form = new Form(package_path('Navigation', '/forms/admin/index-add-item.php'));

        $tree = new Helper\Tree($menu->getAlias());

        $form->parentId->setMultiOptions($tree->flat($tree->getTree(null), '---', array((int) $id)));

        $form->populate($item->toArray());

        if ($this->request->isPost()) {

            $post = $this->request->getPost();

            if (isset($post['btnBack'])) {
                return new RedirectResponse(route(\null, array('action' => 'items', 'id' => \null)));
            }

            $form->isValid($post);

            if (isset($post['alias']) && $post['alias']) {

                $m = new Model\Table\Items();

                $where = array('alias = ?' => $post['alias']);

                if ($item->getId()) {
                    $where['id <> ?'] = $item->getId();
                }

                if ($m->fetchRow($where)) {
                    $form->alias->addError('Псевдонимът се използва');
                    $form->markAsError();
                }
            }

            if (!$form->hasErrors()) {

                if (isset($post['routeData'])) {
                    $routeData = $post['routeData'];
                } else {
                    $routeData = array();
                }

                foreach ($routeData as $k => $v) {
                    if (empty($v)) {
                        unset($routeData[$k]);
                    }
                }

                $post = Utils::arrayMapRecursive('trim', $post, true);

                $item->setFromArray($post);

                $item->setMenuId($menuId);

                if ($item->getRoute() === \null) {
                    if ($item->getUrl() === \null) {
                        $item->setUrl('#');
                    }
                } else {
                    $item->setUrl(\null);
                }

                if ($item->getRoute()) {
                    if (($navigationHelper = $this->getNavigationHelper($item->getRoute())) !== \null) {
                        if (\method_exists($navigationHelper, 'decode')) {
                            $navigationHelper->decode($routeData, $item);
                        }
                    }
                }

                $item->setRouteData(empty($routeData) ? \null : json_encode($routeData));

                if ($item->getOrder() === \null) {
                    if ($item->getParentId()) {
                        $and = ' AND parentId = ' . (int) $item->getParentId();
                    } else {
                        $and = ' AND parentId IS NULL';
                    }
                    $item->setOrder($model->getTable()->getAdapter()->fetchOne('SELECT IFNULL(MAX(`order`), 0) + 1 FROM MenuItems WHERE menuId = ' . (int) $menuId . $and));
                }

                try {

                    $model->save($item);

                    if (isset($post['btnApply'])) {
                        $redirectResponse = new RedirectResponse(route(\null, ['action' => 'add-item', 'id' => $item->getId()]));
                    } else {
                        $redirectResponse = new RedirectResponse(route(\null, ['action' => 'items', 'id' => \null]));
                    }

                    return $redirectResponse->withFlash('Информацията е записана');

                } catch (\Exception $e) {

                    if ($item->getId()) {
                        $redirectResponse = new RedirectResponse(route(\null, ['action' => 'add-item', 'id' => $item->getId()]));
                    } else {
                        $redirectResponse = new RedirectResponse(route(\null, ['action' => 'items', 'id' => \null]));
                    }

                    return $redirectResponse->withFlash((env('development') ? $e->getMessage() : 'Възникна грешка. Опитайте по-късно'), 'danger');
                }
            }
        }

        $this->view->assign('menu', $menu);
        $this->view->assign('item', $item);
        $this->view->assign('form', $form);
    }

    public function ajaxGetRouteDataAction()
    {
        $routeName = $this->request->getPost('route');
        $routeData = $this->request->getPost('routeData');
        $qsaData   = $this->request->getPost('qsaData');

        $routeData = $routeData ? json_decode($routeData, \true) : [];
        $routeData = is_array($routeData) ? $routeData : [];

        $route = $this->container->get('router')->getRoute($routeName);

        if ($route !== \null && \method_exists($route, 'compile')) {
            $route->compile();
        }

        $navigationData = [];

        if (($navigationHelper = $this->getNavigationHelper($routeName)) !== \null) {
            if (method_exists($navigationHelper, 'resolve')) {
                $navigationData = $navigationHelper->resolve($routeData, $route);
            }
        }

        $this->view->assign('route', $route);
        $this->view->assign('routeData', $routeData);
        $this->view->assign('qsaData', $qsaData);
        $this->view->assign('navigationData', $navigationData);
        $this->view->assign('navigationHelper', $navigationHelper);
    }

    protected function getNavigationHelper($routeName)
    {
        $route = $this->container->get('router')->getRoute($routeName);

        if ($route !== \null && ($matches = $this->container->get('resolver')->matchResolve($route->getHandler())) !== \null) {
            $parts = explode('\\', $matches[0]);
            $navigationHelper = \ucfirst(Utils::camelize($parts[0])) . '\\Navigation\\Route\\' . \ucfirst(Utils::camelize($routeName));
            if (\class_exists($navigationHelper, \true)) {
                $navigationHelper = new $navigationHelper($route);
                return $navigationHelper;
            }
        }

        return \null;
    }

    public function deleteItemAction()
    {
        $id = $this->request->getParam('id');

        $model = new Model\Items();

        $entity = $model->find((int) $id);

        if ($entity) {
            $model->delete($entity);
        }

        return new RedirectResponse(route(\null, array('action' => 'items', 'id' => \null)));
    }

    public function ajaxSetOrderAction()
    {
        $data = $this->request->getPost('data', '[]');
        $data = json_decode($data, true);
        $data = is_array($data) ? $data : array();

        try {
            $menuItemsModel = new Model\Table\Items();
            $menuItemsModel->updateTree($data);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => 0]);
        }

        return new JsonResponse(['success' => 1]);
    }
}