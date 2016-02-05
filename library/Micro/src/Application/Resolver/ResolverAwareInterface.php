<?php

namespace Micro\Application\Resolver;

use Micro\Http\Request;
use Micro\Http\Response;

interface ResolverAwareInterface
{
    /**
     * @param Request $request
     */
    public function setRequest(Request $request);

    /**
     * @param Response $response
     */
    public function setResponse(Response $response);
}