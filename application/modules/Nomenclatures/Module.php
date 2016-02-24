<?php

namespace Nomenclatures;

use Micro\Application\Module as BaseModule;

class Module extends BaseModule
{
    public function getConfig()
    {
        return [
            'dependencies' => [
                'services' => [
                    Model\BrandClasses::class => Model\Factory\BrandClassesFactory::class,
                    Model\Continents::class => Model\Factory\ContinentsFactory::class,
                ],
            ],
        ];
    }
}