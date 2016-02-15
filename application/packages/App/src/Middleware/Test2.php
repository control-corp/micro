<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class Test2
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $response->getBody()->write(' [START ' . __METHOD__ . '] ');

        $response = $next($request, $response);

        $response->getBody()->write(' [END ' . __METHOD__ . '] ');

        return $response;
    }
}