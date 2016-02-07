<?php

namespace Micro\Application\Resolver;

use Micro\Http\Request;
use Micro\Http\Response;

interface ResolverInterface
{
    /**
     * @param string $package
     * @param Request $request
     * @param Response $response
     * @param bool $subRequest
     */
    public function resolve($package, Request $request, Response $response, $subRequest);
}