<?php

namespace App\Factory;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class Test implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $o = new \stdClass();
        $o->name = $requestedName;

        return $o;
    }
}