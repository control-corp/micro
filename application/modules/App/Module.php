<?php

namespace App;

use Micro\Application\Module as BaseModule;
use Micro\Container\ContainerInterface;

class Module extends BaseModule
{
    public function boot(ContainerInterface $container)
    {

    }

    public function getConfig()
    {
        return include __DIR__ . '/configs/module.php';
    }
}