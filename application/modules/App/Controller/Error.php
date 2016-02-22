<?php

namespace App\Controller;

use Micro\Http\Response\JsonResponse;
use Micro\Application\Controller;

class Error extends Controller
{
    const ERROR = 'Error !';

    public function init() {}

    public function indexAction()
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

        return $response->write('<pre><strong>' . $exception->getMessage() . "</strong>\n" . $exception->getTraceAsString() . '</pre>')->withStatus($code);
    }
}