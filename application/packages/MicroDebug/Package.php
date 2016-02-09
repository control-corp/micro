<?php

namespace MicroDebug;

use Micro\Application\Package as BasePackage;

class Package extends BasePackage
{
    public function boot()
    {
        (new Handler\FirePHP)->boot();
        (new Handler\Performance)->boot();

        if (!$this->container['request']->isAjax()) {
            (new Handler\DevTools)->boot();
        }
    }
}