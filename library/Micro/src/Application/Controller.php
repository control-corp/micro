<?php

namespace Micro\Application;

use Exception as CoreException;
use Micro\Http\Request;
use Micro\Http\Response;
use Micro\Container\ContainerInterface;

class Controller
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var ContainerInterface
     */
    protected $container;

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
     * @param ContainerInterface $container
     */
    public function __construct(Request $request, Response $response, ContainerInterface $container)
    {
        $this->request = $request;
        $this->response = $response;
        $this->container = $container;

        $this->view = new View();
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