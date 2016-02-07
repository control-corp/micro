<?php

namespace App;

use Micro\Container\ContainerAwareInterface;
use Micro\Container\ContainerAwareTrait;
use Micro\Application\View;

class Index implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function index()
    {
        return new View('index');
    }
}