<?php

namespace Micro\Exception;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ExceptionHandlerInterface
{
    public function handleException(\Exception $e, ServerRequestInterface $request, ResponseInterface $response);
}