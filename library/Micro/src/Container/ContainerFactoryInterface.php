<?php

namespace Micro\Container;

interface ContainerFactoryInterface
{
    public function create(ContainerInterface $container, $service);
}