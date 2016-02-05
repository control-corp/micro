<?php

namespace Micro\Application;

use Exception as CoreException;
use Micro\Http;
use Micro\Container\ContainerAwareInterface;
use Micro\Container\ContainerAwareTrait;

class Controller implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var \Micro\Http\Request
     */
    protected $request;

    /**
     * @var \Micro\Http\Response
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
     * @param \Micro\Http\Request $request
     * @param \Micro\Http\Response $response
     * @param \Micro\Application\View $response
     */
    public function __construct(Http\Request $request, Http\Response $response, View $view = \null)
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
     * @return \Micro\Application\View
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