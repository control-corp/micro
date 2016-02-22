<?php

namespace App;

use Micro\Application\Module as BaseModule;

class Module extends BaseModule
{
    public function getConfig()
    {
        return include __DIR__ . '/Resources/configs/module.php';
    }
}