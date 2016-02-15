<?php

namespace App;

use Micro\Container\ContainerFactoryInterface;
use Micro\Container\ContainerInterface;

class IndexFactory implements ContainerFactoryInterface
{
    public function create(ContainerInterface $container, $service)
    {
        return new Index();
    }
}