<?php

namespace Micro\Application;

use Exception as CoreException;
use Micro\Http\Request;
use Micro\Http\Response;
use Micro\Container\ContainerAwareInterface;
use Micro\Container\ContainerAwareTrait;

class Controller implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var View
     */
    protected $view;

    /**
     * @var string
     */
    protected $scope;

    /**
     * @param Request $request
     * @param Response $response
     * @param View $response
     */
    public function __construct(Request $request, Response $response, View $view = \null)
    {
        $this->request = $request;

        $this->response = $response;

        $this->view = $view ?: new View();
    }

    /**
     * @throws \Exception
     */
    public function init()
    {
        if (!is_allowed()) {
            throw new CoreException('Access denied', 403);
        }
    }

    /**
     * @return View
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }
}