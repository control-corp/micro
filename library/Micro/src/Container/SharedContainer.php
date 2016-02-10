<?php

namespace Micro\Container;

class SharedContainer
{
    /**
     * @var ContainerInterface $instance
     */
    private static $instance;

    /**
     * @param ContainerInterface $instance
     */
    public static function setInstance(ContainerInterface $instance)
    {
        static::$instance = $instance;
    }

    /**
     * @return ContainerInterface
     */
    public static function getInstance()
    {
        if (static::$instance === \null) {
            static::$instance = new Container();
        }

        return static::$instance;
    }
}