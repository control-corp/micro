<?php

namespace App;

use Micro\Application\Resolver\ResolverAwareInterface;
use Micro\Application\Resolver\ResolverAwareTrait;
use Micro\Container\ContainerAwareInterface;
use Micro\Container\ContainerAwareTrait;

class Index implements ResolverAwareInterface, ContainerAwareInterface
{
    use ResolverAwareTrait, ContainerAwareTrait;

    public function index()
    {
        return 'hello';
    }
}