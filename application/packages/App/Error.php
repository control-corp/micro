<?php

namespace App;

use Micro\Http\Response\JsonResponse;
use Micro\Application\Resolver\ResolverAwareInterface;
use Micro\Application\Resolver\ResolverAwareTrait;

class Error implements ResolverAwareInterface
{
    use ResolverAwareTrait;

    const ERROR = 'Error !';

    public function index()
    {
        $exception = $this->request->getParam('exception');

        if (!$exception instanceof \Exception) {
            return ['exception' => $exception, 'message' => static::ERROR];
        }

        $code = $exception->getCode() ?: 404;
        $message = (env('development') || $code === 403 ? $exception->getMessage() : static::ERROR);

        if ($this->request->isAjax()) {
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

        $this->response->withStatus($code);

        return $this->response->write('ERROR: ' . $exception->getMessage())->withStatus($code);
    }
}