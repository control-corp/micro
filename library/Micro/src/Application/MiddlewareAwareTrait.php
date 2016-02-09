<?php

namespace Micro\Application;

use RuntimeException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;

/**
 * Middleware
 */
trait MiddlewareAwareTrait
{
    /**
     * Middleware call stack
     *
     * @var array
     */
    protected $stack;

    /**
     * Middleware stack lock
     *
     * @var bool
     */
    protected $middlewareLock = false;

    /**
     * Add middleware
     *
     * This method prepends new middleware to the application middleware stack.
     *
     * @param callable $callable Any callable that accepts three arguments:
     *                           1. A Request object
     *                           2. A Response object
     *                           3. A "next" middleware callable
     * @return static
     *
     * @throws RuntimeException         If middleware is added while the stack is dequeuing
     * @throws UnexpectedValueException If the middleware doesn't return a Psr\Http\Message\ResponseInterface
     */
    protected function addMiddleware(callable $callable)
    {
        if ($this->middlewareLock) {
            throw new RuntimeException('Middleware can’t be added once the stack is dequeuing');
        }

        if ($this->stack === \null) {
            $this->seedMiddlewareStack();
        }

        $next = array_pop($this->stack);

        $this->stack[] = function (ServerRequestInterface $req, ResponseInterface $res) use ($callable, $next) {

            $result = call_user_func($callable, $req, $res, $next);

            if ($result instanceof ResponseInterface === false) {
                throw new UnexpectedValueException(
                    'Middleware must return instance of \Psr\Http\Message\ResponseInterface'
                );
            }

            return $result;
        };

        return $this;
    }

    /**
     * Seed middleware stack with first callable
     *
     * @param callable $kernel The last item to run as middleware
     *
     * @throws RuntimeException if the stack is seeded more than once
     */
    protected function seedMiddlewareStack(callable $middleware = null)
    {
        if ($this->stack !== \null) {
            throw new RuntimeException('MiddlewareStack can only be seeded once.');
        }

        if ($middleware === \null) {
            $middleware = $this;
        }

        $this->stack = [$middleware];

        return $this;
    }

    /**
     * Call middleware stack
     *
     * @param  ServerRequestInterface $req A request object
     * @param  ResponseInterface      $res A response object
     *
     * @return ResponseInterface
     */
    public function callMiddlewareStack(ServerRequestInterface $req, ResponseInterface $res)
    {
        if ($this->stack === \null) {
            $this->seedMiddlewareStack();
        }

        $start = array_pop($this->stack);

        $this->middlewareLock = \true;

        $resp = $start($req, $res);

        $this->middlewareLock = \false;

        return $resp;
    }
}
