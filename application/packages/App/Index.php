<?php

namespace App;

use Micro\Application\View;
use Micro\Container\ContainerInterface;
use Micro\Http\Request;
use Micro\Http\Response;

class Index
{
    public function index(
        Request $request,
        Response $response,
        ContainerInterface $container
    ) {
        return new View('index');
    }
}