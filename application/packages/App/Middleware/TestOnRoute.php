<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class TestOnRoute
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $response->write('[' . __CLASS__ . ' start] ');

        $response = $next($request, $response);

        $response->write(' [' . __CLASS__ . ' end]');

        return $response;
    }
}