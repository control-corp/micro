<?php

namespace Micro\Application\Controller;

use Micro\Application\Utils;
use Micro\Model\ModelInterface;

trait DetectModelTrait
{
    /**
     * @var \Micro\Model\ModelInterface
     */
    protected $model;

    /**
     * @throws \Exception
     * @return \Micro\Model\ModelInterface
     */
    public function getModel()
    {
        if ($this->model === \null) {
            $package = $this->request->getParam('package');
            $controller = $this->request->getParam('controller');
            if ($package && $controller) {
                $package = ucfirst(Utils::camelize($package));
                $controller = ucfirst(Utils::camelize($controller));
                $model = $package . '\Model\\' . $controller;
                if (class_exists($model, \true)) {
                    $this->model = new $model;
                } else {
                    $model = $package . '\Model\\' . $package;
                    if (class_exists($model, \true)) {
                        $this->model = new $model;
                    }
                }
            }
        } else if (is_string($this->model) && class_exists($this->model, \true)) {
            $this->model = new $this->model;
        }

        if (!$this->model instanceof ModelInterface) {
            throw new \Exception(sprintf(
                'Model [%s] must be instanceof %s',
                (is_object($this->model) ? get_class($this->model) : gettype($this->model)),
                ModelInterface::class
            ));
        }

        return $this->model;
    }
}