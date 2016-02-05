<?php

namespace Micro\Exception;

use Micro\Http\Request;
use Micro\Http\Response;

interface ExceptionHandlerInterface
{
    public function handleException(\Exception $e, Request $request, Response $response);
}