<?php

namespace App\Controller\Admin;

use Micro\Http\Response\JsonResponse;
use Micro\Auth\Auth;
use Micro\Http\Response\RedirectResponse;
use Micro\Application\Controller;

class Error extends Controller
{
    const ERROR = 'Error !';

    protected $scope = 'admin';

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

        if ($exception->getCode() === 403) {
            if (Auth::identity() === \null) {
                if (is_allowed($this->container['router']->getRoute('admin-login')->getHandler())) {
                    return new RedirectResponse(
                        route('admin-login', ['backTo' => urlencode(route())])
                    );
                }
            }
        }

        return ['exception' => $exception, 'message' => $message];
    }
}