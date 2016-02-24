<?php

namespace Nomenclatures\Model\Factory;

use Micro\Container\ContainerFactoryInterface;
use Micro\Container\ContainerInterface;
use Nomenclatures\Model\BrandClasses as Model;
use Nomenclatures\Model\Table\BrandClasses as Table;
use Nomenclatures\Model\Entity\BrandClass as Entity;

class BrandClassesFactory implements ContainerFactoryInterface
{
    /**
     * {@inheritDoc}
     * @see \Micro\Container\ContainerFactoryInterface::create()
     */
    public function create(ContainerInterface $container, $service)
    {
        return new Model(
            new Table(
                $container->get('db')
            ),
            Entity::class,
            $container->get('event'),
            $container->get('cache')
        );
    }
}