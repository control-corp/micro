<?php

namespace App\Service;

use Micro\Container\ContainerInterface;

class TestFactory
{
    public function create(ContainerInterface $container, $service)
    {
        return new Test($container->get('logger'));
    }
}