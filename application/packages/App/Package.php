<?php

namespace App;

use Micro\Application\Package as BasePackage;
use Micro\Container\ContainerInterface;

class Package extends BasePackage
{
    public function boot(ContainerInterface $container)
    {

    }

    public function getConfig()
    {
        return include __DIR__ . '/configs/package.php';
    }
}