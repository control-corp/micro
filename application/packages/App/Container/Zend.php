<?php

namespace App\Container;

use Zend\ServiceManager\ServiceManager;
use Micro\Container\ContainerInterface;
use Micro\Container\SharedContainer;

class Zend extends ServiceManager implements ContainerInterface
{
    public function __construct(array $config = [], $useAsDefault = \true)
    {
        parent::__construct($config);

        if ($useAsDefault === \true) {
            SharedContainer::setInstance($this);
        }
    }

    public function set($id, $callback)
    {
        $this->setFactory($id, $callback);

        return $this;
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