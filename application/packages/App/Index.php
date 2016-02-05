<?php

namespace App;

use Micro\Application\Resolver\ResolverAwareInterface;
use Micro\Application\Resolver\ResolverAwareTrait;

class Index implements ResolverAwareInterface
{
    use ResolverAwareTrait;

    public function index()
    {
        return 'hello';
    }
}