<?php

namespace App;

use Micro\Container\ContainerInterface;
use Micro\Http\Request;
use Micro\Http\Response;

class Api
{
    public function index(
        Request $request,
        Response $response,
        ContainerInterface $container
    ) {
        return __METHOD__;
    }

    public function __call($method, $args)
    {
        throw new \Exception(__METHOD__ . '::' . $method . ' not found');
    }
}