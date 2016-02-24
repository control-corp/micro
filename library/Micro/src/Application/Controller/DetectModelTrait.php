<?php

namespace Micro\Application\Controller;

use Micro\Application\Utils;
use Micro\Model\ModelInterface;

trait DetectModelTrait
{
    /**
     * @var ModelInterface
     */
    protected $model;

    /**
     * @throws \Exception
     * @return ModelInterface
     */
    public function getModel()
    {
        $modelCandidate = \null;

        if ($this->model === \null) {
            $module = $this->request->getParam('module');
            $controller = $this->request->getParam('controller');
            if ($module && $controller) {
                $module = ucfirst(Utils::camelize($module));
                $controller = ucfirst(Utils::camelize($controller));
                $model = $module . '\Model\\' . $controller;
                if (\class_exists($model, \true)) {
                    $modelCandidate = $model;
                } else {
                    $model = $module . '\Model\\' . $module;
                    if (\class_exists($model, \true)) {
                        $modelCandidate = $model;
                    }
                }
            }
        } else if (\is_string($this->model) && \class_exists($this->model, \true)) {
            $modelCandidate = $this->model;
        }

        if ($modelCandidate !== \null) {
            if ($this->container->has($modelCandidate)) {
                $this->model = $this->container->get($modelCandidate);
            } else {
                $this->model = new $modelCandidate;
            }
        }

        if (!$this->model instanceof ModelInterface) {
            throw new \Exception(\sprintf(
                'Model [%s] must be instanceof %s',
                (is_object($this->model) ? get_class($this->model) : (\is_string($this->model) ? $this->model : \gettype($this->model))),
                ModelInterface::class
            ));
        }

        return $this->model;
    }
}