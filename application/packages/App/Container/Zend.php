<?php

namespace App\Container;

use Zend\ServiceManager\ServiceManager;
use Micro\Container\ContainerInterface;
use Micro\Container\SharedContainer;
use Micro\Container\ContainerAwareInterface;

class Zend extends ServiceManager implements ContainerInterface
{
    private $awared = [];

    public function __construct(array $config = [], $useAsDefault = \true)
    {
        parent::__construct($config);

        if ($useAsDefault === \true) {
            SharedContainer::setInstance($this);
        }
    }

    public function set($id, $callback)
    {
        if (is_object($callback) && !$callback instanceof \Closure) {
            $this->setService($id, $callback);
        } else {
            $this->setFactory($id, $callback);
        }

        return $this;
    }

    public function get($name)
    {
        $service = parent::get($name);

        if (!isset($this->awared[$name]) && $service instanceof ContainerAwareInterface) {
            $service->setContainer($this);
            $this->awared[$name] = \true;
        }

        return $service;
    }

    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetGet()
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $value)
    {
        return $this->set($offset, $value);
    }

    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetExists()
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset)
    {

    }
}