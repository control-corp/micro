<?php

namespace Micro\Container;

interface ContainerAwareInterface
{
    public function setContainer(ContainerInterface $container);
}