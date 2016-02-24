<?php

namespace Nomenclatures\Model\Factory;

use Micro\Container\ContainerFactoryInterface;
use Micro\Container\ContainerInterface;
use Nomenclatures\Model\Continents as ModelContinents;
use Nomenclatures\Model\Table\Continents as TableContinents;
use Nomenclatures\Model\Entity\Continent as EntityContinents;

class ContinentsFactory implements ContainerFactoryInterface
{
    /**
     * {@inheritDoc}
     * @see \Micro\Container\ContainerFactoryInterface::create()
     */
    public function create(ContainerInterface $container, $service)
    {
        return new ModelContinents(
            new TableContinents(
                $container->get('db')
            ),
            EntityContinents::class,
            $container->get('event'),
            $container->get('cache')
        );
    }
}