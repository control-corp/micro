<?php

namespace App;

use Micro\Http\Response\JsonResponse;
use Micro\Container\ContainerAwareInterface;
use Micro\Container\ContainerAwareTrait;

class Error implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const ERROR = 'Error !';

    public function index()
    {
        $request = $this->container->get('request');
        $response = $this->container->get('response');

        $exception = $request->getParam('exception');

        if (!$exception instanceof \Exception) {
            return ['exception' => $exception, 'message' => static::ERROR];
        }

        $code = $exception->getCode() ?: 404;
        $message = (env('development') || $code === 403 ? $exception->getMessage() : static::ERROR);

        if ($request->isAjax()) {
            return new JsonResponse([
                'error' => [
                    'message' => $exception->getMessage(),
                    'code'    => $exception->getCode(),
                    'file'    => $exception->getFile(),
                    'line'    => $exception->getLine(),
                    'trace'   => $exception->getTrace()
                ]
            ], $code);
        }

        $response->withStatus($code);

        return $response->write('ERROR: ' . $exception->getMessage())->withStatus($code);
    }
}