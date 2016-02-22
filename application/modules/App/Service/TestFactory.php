<?php

namespace App\Service;

use Micro\Container\ContainerInterface;
use Micro\Container\ContainerFactoryInterface;

class TestFactory implements ContainerFactoryInterface
{
    public function create(ContainerInterface $container, $service)
    {
        return new Test($container->get('logger'));
    }
}