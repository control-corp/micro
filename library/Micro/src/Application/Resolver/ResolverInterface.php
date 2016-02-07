<?php

namespace Micro\Application\Resolver;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ResolverInterface
{
    /**
     * @param string $package
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param bool $subRequest
     */
    public function resolve($package, ServerRequestInterface $request, ResponseInterface $response, $subRequest);
}