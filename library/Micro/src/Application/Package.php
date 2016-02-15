<?php

namespace Micro\Application;

use Micro\Container\ContainerInterface;

abstract class Package
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $dir;

    /**
     * Some initializations like config, services and events
     */
    public function boot(Application $app, ContainerInterface $container)
    {}

    /**
     * @return string $name
     */
    final public function initName ()
    {
        if ($this->name === \null) {
            $module = get_class($this);
            $module = explode('\\', $module);
            $this->name = $module[0];
        }

        return $this->name;
    }

    /**
     * @return string $name
     */
    final public function getName ()
    {
        $this->initName();

        return $this->name;
    }

    /**
     * @return string $dir
     */
    final public function initDir ()
    {
        if ($this->dir === \null) {
            $r = new \ReflectionClass($this);
            $this->dir = dirname($r->getFileName());
        }

        return $this->dir;
    }

    /**
     * @return string $dir
     */
    final public function getDir ()
    {
        $this->initDir();

        return $this->dir;
    }
}