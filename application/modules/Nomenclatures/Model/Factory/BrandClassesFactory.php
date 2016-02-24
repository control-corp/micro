<?php

namespace Nomenclatures\Model\Factory;

use Micro\Container\ContainerFactoryInterface;
use Micro\Container\ContainerInterface;
use Nomenclatures\Model\BrandClasses as ModelBrandClasses;
use Nomenclatures\Model\Table\BrandClasses as TableBrandClasses;
use Nomenclatures\Model\Entity\BrandClass as EntityBrandClasses;

class BrandClassesFactory implements ContainerFactoryInterface
{
    /**
     * {@inheritDoc}
     * @see \Micro\Container\ContainerFactoryInterface::create()
     */
    public function create(ContainerInterface $container, $service)
    {
        return new ModelBrandClasses(
            new TableBrandClasses(
                $container->get('db')
            ),
            EntityBrandClasses::class,
            $container->get('event'),
            $container->get('cache')
        );
    }
}