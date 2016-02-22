<?php

namespace Micro\Application\Controller;

use Micro\Application\Controller;
use Micro\Application\View;
use Micro\Http\Response\RedirectResponse;
use Micro\Form\Form;
use Micro\Grid;
use Micro\Application\Utils;
use Micro\Model\EntityInterface;
use Micro\Http\Response;
use Micro\Translator\Language\LanguageInterface;

class Crud extends Controller
{
    use DetectModelTrait;

    protected $ipp = 10;

    /**
     * @return RedirectResponse|View
     */
    public function indexAction()
    {
        $module = $this->request->getParam('module');
        $controller = $this->request->getParam('controller');

        if ($this->request->isPost()) {
            if ($this->request->getPost('btnAdd')) {
                return new RedirectResponse(route(\null, ['action' => 'add', 'id' => \null, 'page' => \null]));
            }
        }

        if (($filters = $this->handleFilters()) instanceof Response) {
            return $filters;
        }

        $model = $this->getModel();

        if ($this->container->has('language') && ($language = $this->container->get('language')) instanceof LanguageInterface) {
            $model->addJoinCondition('languageId', $language->getId());
        }

        $model->addFilters($filters);

        // hook
        $this->modifyModel($filters);

        $ipp = max($this->ipp, $this->request->getParam('ipp', $this->ipp));
        $page = max(1, $this->request->getParam('page', 1));
        $sort = $this->request->getParam('sort', $model->getIdentifier() . '_DESC');

        $sortParts = explode('_', $sort);

        $orderField = array_shift($sortParts);
        $orderField = $orderField ?: $model->getIdentifier();

        $orderDir = array_shift($sortParts);
        $orderDir = $orderDir ? strtoupper($orderDir) : 'DESC';

        $model->addOrder($orderField, $orderDir);

        $grid = new Grid\Grid(
            $model,
            module_path(ucfirst(Utils::camelize($module)), '/Resources/grids/' . ($this->scope ? $this->scope . '/' : '') . $controller . '.php')
        );

        $grid->getRenderer()->setView($this->view);

        $column = $grid->getColumn($orderField);

        if ($column instanceof Grid\Column) {
            $column->setSorted($orderDir);
        }

        $grid->setIpp($ipp);
        $grid->setPageNumber($page);

        $this->view->setTemplate(($this->scope ? $this->scope . '/' : '') . $controller . '/index');

        return $this->view->assign(['grid' => $grid, 'filters' => $filters]);
    }

    /**
     * @param array $filters
     */
    protected function modifyModel(array $filters)
    {
    }

    /**
     * @param EntityInterface $entity
     * @return RedirectResponse|View
     */
    public function addAction(EntityInterface $entity = \null)
    {
        $module = $this->request->getParam('module');
        $controller = $this->request->getParam('controller');

        $model = $this->getModel();

        if ($entity === \null) {
            $entity = $model->createEntity();
        }

        $form = new Form(module_path(ucfirst(Utils::camelize($module)), '/Resources/forms/' . ($this->scope ? $this->scope . '/' : '') . $controller . '-add.php'));

        $form->populate($entity->toArray());

        // hook
        $this->prepareForm($form, $entity);

        if ($this->request->isPost()) {

            $post = $this->request->getPost();

            if (isset($post['btnBack'])) {
                return new RedirectResponse(route(\null, ['action' => 'index', 'id' => \null]));
            }

            $post = Utils::arrayMapRecursive('trim', $post);

            // hook
            $this->modifyPost($form, $entity, $post);

            // hook
            $this->preValidate($form, $entity, $post);

            $form->isValid($post);

            // hook
            $this->postValidate($form, $entity, $post);

            if (!$form->hasErrors()) {

                if (!isset($post['languageId']) && $this->container->has('language') && ($language = $this->container->get('language')) instanceof LanguageInterface) {
                    $post['languageId'] = $language->getId();
                }

                try {

                    $post = Utils::arrayMapRecursive('trim', $post, true);

                    // hook
                    $this->modifyData($post);

                    $entity->setFromArray($post);

                    // hook
                    $this->modifyEntity($entity);

                    $model->save($entity);

                    if (isset($post['btnApply'])) {
                        $redirectResponse = new RedirectResponse(route(\null, ['action' => 'edit', 'id' => $entity[$model->getIdentifier()]]));
                    } else {
                        $redirectResponse = new RedirectResponse(route(\null, ['action' => 'index', 'id' => \null]));
                    }

                    return $redirectResponse->withFlash('Информацията е записана');

                } catch (\Exception $e) {

                    if ($entity[$model->getIdentifier()]) {
                        $redirectResponse = new RedirectResponse(route(\null, ['action' => 'edit', 'id' => $entity[$model->getIdentifier()]]));
                    } else {
                        $redirectResponse = new RedirectResponse(route(\null, ['action' => 'add', 'id' => \null]));
                    }

                    return $redirectResponse->withFlash((env('development') ? $e->getMessage() : 'Възникна грешка. Опитайте по-късно'), 'danger');
                }
            }
        }

        $this->view->setTemplate(($this->scope ? $this->scope . '/' : '') . $controller . '/add');

        return $this->view->assign(['form' => $form, 'item' => $entity]);
    }

    /**
     * @param Form $form
     * @param EntityInterface $entity
     */
    protected function prepareForm(Form $form, EntityInterface $entity)
    {
    }

    /**
     * @param Form $form
     * @param EntityInterface $entity
     * @param array $data
     */
    protected function modifyPost(Form $form, EntityInterface $item, array &$data)
    {
    }

    /**
     * @param Form $form
     * @param EntityInterface $entity
     * @param array $data
     */
    protected function preValidate(Form $form, EntityInterface $item, array $data)
    {

    }

    /**
     * @param Form $form
     * @param EntityInterface $entity
     * @param array $data
     */
    protected function postValidate(Form $form, EntityInterface $item, array $data)
    {
    }

    /**
     * @param array $data
     */
    protected function modifyData(array &$data)
    {
    }

    /**
     * @param EntityInterface $entity
     */
    protected function modifyEntity(EntityInterface $entity)
    {
    }

    /**
     * @throws \Exception
     * @return \Micro\Http\Response\RedirectResponse|\Micro\Application\View
     */
    public function editAction()
    {
        $model = $this->getModel();

        if ($this->container->has('language') && ($language = $this->container->get('language')) instanceof LanguageInterface) {
            $model->addJoinCondition('languageId', $language->getId());
        }

        $entity = $model->find((int) $this->request->getParam('id', 0));

        if ($entity === \null) {
            throw new \Exception(sprintf('Записът не е намерен'), 404);
        }

        return $this->addAction($entity);
    }

    /**
     * @return \Micro\Http\Response\RedirectResponse
     */
    public function deleteAction()
    {
        $id = (int) $this->request->getParam('id', 0);
        $ids = $this->request->getParam('ids', []);

        if ($id) {
            $ids = [$id];
        }

        $ids = array_filter($ids);

        $affected = 0;

        if (!empty($ids)) {
            $model = $this->getModel();
            $model->addWhere('id', $ids);
            $items = $model->getItems();
            foreach ($items as $item) {
                try {
                    $affected += $model->delete($item);
                } catch (\Exception $e) {

                }
            }
        }

        $redirectResponse = new RedirectResponse(route(\null, ['action' => 'index', 'id' => \null, 'ids' => \null]));

        return $redirectResponse->withFlash(sprintf('Информацията е записана. Бяха изтрити %d запис(а)', $affected));
    }

    /**
     * Activate item Action
     * @param number $active
     */
    public function activateAction($active = 1)
    {
        $id = (int) $this->request->getParam('id');
        $ids = $this->request->getParam('ids', []);

        if ($id) {
            $ids = array($id);
        }

        $ids = array_filter($ids);

        $affected = 0;

        if (!empty($ids)) {
            $model = $this->getModel();
            $model->addWhere('id', $ids);
            $items = $model->getItems();
            foreach ($items as $item) {
                try {
                    $affected += $model->activate($item, $active);
                } catch (\Exception $e) {

                }
            }
        }

        $redirectResponse = new RedirectResponse(route(\null, ['action' => 'index', 'id' => \null, 'ids' => \null]));

        return $redirectResponse->withFlash(sprintf('Информацията е записана. Бяха %s %d запис(а)', ($active ? 'активирани' : 'деактивирани'), $affected));
    }

    /**
     * Deactivate item Action
     */
    public function deactivateAction()
    {
        return $this->activateAction(0);
    }

    /**
     * Handle filters
     * @param string $key
     * @return \Micro\Http\Response\RedirectResponse|array
     */
    protected function handleFilters($key = 'filters', $clearParams = ['id' => \null, 'page' => \null, 'sort' => \null])
    {
        $filters = $this->request->getParam($key);

        if ($this->request->isPost()) {

            $post = $this->request->getPost($key, []);

            if (isset($post['reset'])) {
                return new RedirectResponse(route(\null, [$key => \null] + $clearParams));
            }

            if (isset($post['filter'])) {
                unset($post['filter']);
                foreach ($post as $k => $v) {
                    if (is_object($v)
                        || (is_array($v) && empty($v))
                        || \trim((string) $v) === ''
                    ) {
                        unset($post[$k]);
                    }
                }
                return new RedirectResponse(route(\null, [$key => (!empty($post) ? Utils::base64urlEncode(http_build_query($post)) : \null)] + $clearParams));
            }
        }

        if ($filters) {
            parse_str(Utils::base64urlDecode($filters, \true), $filters);
            if (empty($filters)) {
                $filters = [];
            }
        } else {
            $filters = [];
        }

        return $filters;
    }
}